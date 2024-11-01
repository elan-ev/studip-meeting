<?php

namespace Meetings\Helpers;
use Meetings\Models\I18N;
use ElanEv\Model\Meeting;
use Meetings\Errors\Error;
use Nyholm\Psr7\UploadedFile;

use Course;
use StudipNews;
use TCPDF_FONTS;
use TCPDF;
use Flexi\Factory as TemplateFactory;
use ExportPDF;
use setasign\Fpdi\Tcpdf\Fpdi;
/**
 * DefaultSlideHelper.php - contains function to handle news & announcements to show as default slide,
 * as well as all the default slide functions to manage by admin.
 *
 * @author Farbod Zamani Boroujeni (zamani@elan-ev.de)
 */

define('TEMPLATES_DIR', dirname(__DIR__, 2) . '/templates');
define('SAMPLES_DIR', TEMPLATES_DIR . '/samples/');
define('MEETING_DOC_DIRNAME', 'meetings_doc');

class DefaultSlideHelper {

    private $font_dir;
    private $pages_dir;

    /**
     * Returns the DefaultSlideHelper singleton instance.
     */
    public static function getInstance()
    {
        static $instance;

        if (isset($instance)) {
            return $instance;
        }

        return $instance = new DefaultSlideHelper();
    }

    /**
     * Initialize a new DefaultSlideHelper instance.
     */
    private function __construct()
    {
        $this->prepareUploadDirectories();
    }

    /**
     * Uses the /data directory by default to create the meeting docs dir,
     * If data directory is not available, then it tries to create the meeting dir,
     * beside the UPLOAD_PATH directory!
     *
     * @return string|boolean meeting docs path or false if the unable to make the dir
     * @throws Error
     */
    private function getMeetingUploadPath()
    {
        try {
            $data_dir = rtrim($GLOBALS['STUDIP_BASE_PATH'], '/') . '/data';
            if (!is_dir($data_dir) && is_dir($GLOBALS['UPLOAD_PATH'])) {
                $data_dir = dirname($GLOBALS['UPLOAD_PATH'], 1);
            }

            if (empty($data_dir)) {
                throw new Error(I18N::_('Es konnte kein Datenpfad gefunden werden!'), 500);
            }

            $meetings_doc_dir = rtrim($data_dir, '/') . '/' . MEETING_DOC_DIRNAME;
            if (is_writeable($data_dir) && !is_dir($meetings_doc_dir) && $GLOBALS['perm']->have_perm('admin')) {
                @mkdir($meetings_doc_dir);
            }

            return is_dir($meetings_doc_dir) ? $meetings_doc_dir : false;

        } catch (\Throwable $th) {
            throw new Error(I18N::_('Dateisystemeintrag konnte nicht erstellt werden!'), 500, $th->getMessage());
        }
    }

    /**
     * Makes sure that Upload directories are prepared, if not it creates them.
     */
    private function prepareUploadDirectories()
    {
        try {
            if ($meeting_doc_upload_path = $this->getMeetingUploadPath()) {
                $this->font_dir = $meeting_doc_upload_path . '/font';
                $this->pages_dir = $meeting_doc_upload_path . '/page';

                // Fonts
                if (!is_dir($this->font_dir)) {
                    @mkdir($this->font_dir);
                    @mkdir($this->font_dir . '/regular');
                    @mkdir($this->font_dir . '/bold');
                    @mkdir($this->font_dir . '/italic');
                    @mkdir($this->font_dir . '/bold_italic');
                }

                // Pages
                if (!is_dir($this->pages_dir)) {
                    @mkdir($this->pages_dir);
                }
            }
        } catch (\Throwable $th) {
            throw new Error(I18N::_('Standard-Folienordner konnten nicht erstellt werden!'), 500, $th->getMessage());
        }
    }

    /**
     * Double checks if the directory exists.
     *
     * @param string $dir_name the name of installed directory
     *
     * @return boolean whether it exists or not
     */
    private function doubleCheckDirectories($dir_name)
    {
        if ($dir_name == 'page') {
            return !empty($this->pages_dir)
                && strpos($this->pages_dir, MEETING_DOC_DIRNAME) !== false
                && is_dir($this->pages_dir);
        }

        if ($dir_name == 'font') {
            return !empty($this->font_dir)
                && strpos($this->font_dir, MEETING_DOC_DIRNAME) !== false
                && is_dir($this->font_dir);
        }

        return false;
    }

    /**
     * Get all news from course and studip
     *
     * @param string $courseid the meeting object
     * @param int $limit the number of news to show, default is 3.
     *
     * @return array
     */
    public function getNewsList($range = 'studip', $limit = 3)
    {
        $news = [];

        $news = StudipNews::getNewsByRange($range, true, false);

        if (count($news) > $limit) {
            $news = array_slice($news, 0, $limit);
        }

        return $news;
    }

    /**
     * Get all required texts to replace in intro template
     *
     * @param Meeting the meeting object
     *
     * @return array
     */
    public function getIntroTexts(Meeting $meeting)
    {
        $course = $meeting->courses[0];
        $texts = [
            'welcome' => I18N::_('Willkommen zur Videokonferenz'),
            'meeting_name' => $meeting->name,
            'course_name' => $course->name,
            'course_news_title' => I18N::_('Veranstaltungsankündigungen'),
            'studip_news_title' => I18N::_('Allgemeine Ankündigungen'),
        ];
        return $texts;
    }

    /**
     * It creates dummy array list of news to be shown in preview feature
     *
     * @return array $dummy dummy news array
     */
    private function createDummyNewsList()
    {
        $topic = I18N::_('Nachrichtenthema');
        $body = I18N::_('Nachrichteninhalt');
        $author = 'Test StudIP';
        $date = date("d.m.Y H:i");
        $news = [];
        for ($i = 0; $i < 3; $i++) {
            $news[$i] = [
                'topic' => $topic . ($i + 1),
                'body' => $body,
                'author' => $author,
                'date' => $date,
            ];
        }
        return $news;
    }

    /**
     * Returns HTML of the template by generating the Flexi_Template object and sets attributes based on configs
     *
     * @param string $template_factory_dir the directory path of templates
     * @param string $template_name the name of the template to use
     * @param Meeting $meeting the meeting object
     * @param boolean $dummy the flag which makes preveiw feature more efficient
     *
     * @return string rendered html from template
     */
    private function getFlexiTemplateHTML($template_factory_dir, $template_name, Meeting $meeting, $dummy = false)
    {
        $php_temp_path = '';
        $processed_template_name = $template_name;
        if ($template_factory_dir != TEMPLATES_DIR) {
            $php_temp_path = "$template_factory_dir/_template.php";
            $content = file_get_contents("$template_factory_dir/$template_name");
            $decode_content = base64_decode($content);
            file_put_contents($php_temp_path, $decode_content);
            $processed_template_name = '_template.php';
        }

        $courseid = $meeting->courses[0]->seminar_id;
        $template_factory = new TemplateFactory($template_factory_dir);
        $template = $template_factory->open($processed_template_name);
        if (!$template) {
            $this->removeFile($php_temp_path);
            return false;
        }

        $template->set_attribute('texts', $this->getIntroTexts($meeting));
        $features = json_decode($meeting->features, true);
        $show_course_news = $features && isset($features['default_slide_course_news']) ? filter_var($features['default_slide_course_news'], FILTER_VALIDATE_BOOLEAN) : false;
        $show_studip_news = $features && isset($features['default_slide_studip_news']) ? filter_var($features['default_slide_studip_news'], FILTER_VALIDATE_BOOLEAN) : false;

        $course_news = $this->getNewsList($courseid);
        if ($show_course_news && !empty($course_news)) {
            $template->set_attribute('course_news', $course_news);
        }

        $studip_news = $this->getNewsList();
        if ($show_studip_news && !empty($studip_news)) {
            $template->set_attribute('studip_news', $studip_news);
        }

        // Handle preview.
        if ($dummy) {
            $dummy_news = $this->createDummyNewsList();
            $template->set_attribute('course_news', $dummy_news);
            $template->set_attribute('studip_news', $dummy_news);
        }

        $html = $template->render();
        $this->removeFile($php_temp_path);
        return $html;
    }

    /**
     * It generates the pdf from the uploaded slides and templates by admin
     *
     * @param Meeting $meeting the meeting object
     *
     * @return Fpdi $pdf the generated pdf object
     */
    public function generateCustomizedPDF(Meeting $meeting)
    {
        $pdf = new Fpdi();

        $pdf = $this->installFont($pdf);

        $pages = $this->getInstalledTemplates();
        foreach ($pages as $page) {
            // The page will apply only when there is a pdf file to replace!
            if (isset($page['pdf']) && file_exists("{$page['pdf']['dirname']}/{$page['pdf']['basename']}")) {
                $pdf_path = "{$page['pdf']['dirname']}/{$page['pdf']['basename']}";
                $slide_page_count = $pdf->setSourceFile($pdf_path);
                // Check if the uploaded pdf file has more than one page!
                for ($slide_page = 1; $slide_page <= $slide_page_count; $slide_page++) {
                    $template_id = $pdf->importPage($slide_page);
                    $size = $pdf->getTemplateSize($template_id);
                    $pdf->AddPage($size['orientation'], $size);
                    $pdf->useTemplate($template_id);

                    // Apply php templates, if only the template exists and the it is the first page of uploaded pdf!
                    if (isset($page['php']) && file_exists("{$page['php']['dirname']}/{$page['php']['basename']}") && $slide_page == 1) {
                        $template_html = $this->getFlexiTemplateHTML($page['php']['dirname'], $page['php']['basename'], $meeting);
                        if (empty($template_html)) {
                            continue;
                        }

                        $pdf->writeHTML($template_html);
                    }
                }
            }
        }

        return $pdf;
    }

    /**
     * It generates the default StudIP supported PDF and renders the template in it
     *
     * @param Meeting $meeting the meeting object
     *
     * @return ExportPDF $pdf the generated TCPDF pdf object
     */
    public function generateStudIPDefaultPDF(Meeting $meeting)
    {
        $template_html = $this->getFlexiTemplateHTML(TEMPLATES_DIR, 'default_slide.php', $meeting);
        if (empty($template_html)) {
            return;
        }

        $pdf = new ExportPDF();

        $pdf = $this->installFont($pdf);

        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        $pdf->addPage('L', '', true, false);

        $pdf->writeHTML($template_html, true, false, true, false, '');

        $pdf->lastPage();

        // Adding a blank page as a white board!
        $pdf->addPage('L', '', false, false);

        return $pdf;
    }

    /**
    * Returns installed (uploaded) font with ttf extension
    *
    * @return array $font all types of installed font
    */
    public function getInstalledFont()
    {
        $font = [];
        $font_types = scandir($this->font_dir);
        foreach ($font_types as $type) {
            if ($type == '.' || $type == '..') {
                continue;
            }
            $font_type_obj['type'] = $type;
            $font_type_path = rtrim($this->font_dir, '/') . '/' . $type;
            $ttf_font = glob("$font_type_path/*.ttf");
            $font_base_name = '';
            if (!empty($ttf_font)) {
                $font_base_name = basename($ttf_font[0]);
            }
            $font_type_obj['name'] = $font_base_name;
            $font[] = $font_type_obj;
        }

        $types = array_column($font, 'type');
        array_multisort($types, SORT_DESC, $font);

        return $font;
    }

    /**
     * It installs the font using TCPDF_FONTS class which generates a font php file to be used by TCPDF in AddFont class.
     *
     * @param TCPDF $pdf the generated pdf object
     *
     * @return TCPDF $pdf
     */
    public function installFont(TCPDF $pdf)
    {
        $font_size = 16;

        $installed_font_name = defined('PDF_FONT_NAME_MAIN') ? PDF_FONT_NAME_MAIN : 'helvetica';

        // Regular Font
        $regular_font_file = $this->installFontFileByType('regular');
        // Regular font is required!
        if (empty($regular_font_file)) {
            $pdf->SetFont($installed_font_name, '', $font_size);
            return $pdf;
        }
        $installed_font_name = pathinfo($regular_font_file, PATHINFO_FILENAME);

        $pdf->AddFont($installed_font_name, '', $regular_font_file);

        // Bold Font.
        $bold_font_file = $this->installFontFileByType('bold');
        if (empty($bold_font_file)) {
            $bold_font_file = $regular_font_file;
        }
        $pdf->AddFont($installed_font_name, 'B', $bold_font_file);

        // Italic Font.
        $italic_font_file = $this->installFontFileByType('italic');
        if (empty($italic_font_file)) {
            $italic_font_file = $regular_font_file;
        }
        $pdf->AddFont($installed_font_name, 'I', $bold_font_file);

        // Bold Italic Font
        $bolditalic_font_file = $this->installFontFileByType('bold_italic');
        if (empty($bolditalic_font_file)) {
            $bolditalic_font_file = $regular_font_file;
        }
        $pdf->AddFont($installed_font_name, 'BI', $bold_font_file);

        $pdf->SetFont($installed_font_name, '', $font_size);
        return $pdf;
    }

    /**
     * Installs the uploaded font into TCPDF_FONTS by type
     *
     * @param string $type type of font
     *
     * @return string installed font path or empty
     */
    private function installFontFileByType($type)
    {
        if (!$this->doubleCheckDirectories('font')) {
            return '';
        }
        $font_type_dir_path = rtrim($this->font_dir, '/') . "/$type";
        if (!in_array($type, ['bold', 'regular', 'italic', 'bold_italic']) || !is_dir($font_type_dir_path)) {
            return '';
        }

        $php_files = glob("$font_type_dir_path/*.php");
        $font_php_file = !empty($php_files) ? $php_files[0] : '';

        if (!empty($font_php_file)) { // If it is already installed!
            return $font_php_file;
        }

        $ttf_files = glob("$font_type_dir_path/*.ttf");
        $font_ttf_file = !empty($ttf_files) ? $ttf_files[0] : '';

        if (empty($font_ttf_file)) { // If there is nothing there!
            return '';
        }

        // if ttf file is there and php file does not still exists, means that it needs to be installed by TCPDF_FONTS!
        $font = TCPDF_FONTS::addTTFfont($font_ttf_file, 'TrueTypeUnicode', '', 96, $font_type_dir_path . '/');
        return (file_exists("$font_type_dir_path/{$font}.php")) ? "$font_type_dir_path/{$font}.php" : '';
    }

    /**
    * Reads all the available folders in pages directory are returns an array of templates based on existing folder in pages dir.
    *
    * @return array $templates
    */
    public function getInstalledTemplates()
    {
        // Optimizing base url.
        $base_url = sprintf(
            "%s://%s",
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['SERVER_NAME']
        );

        if (!$this->doubleCheckDirectories('page')) {
            return [];
        }

        $pages_directory = rtrim($this->pages_dir, '/');
        $pages = scandir($pages_directory);
        $templates = [];
        foreach ($pages as $page) {
            if ($page != '.' && $page != '..' && is_dir("$pages_directory/$page")) {
                $pdf_file = glob("$pages_directory/$page/*.pdf");
                if (!empty($pdf_file)) {
                    $templates[$page]['pdf'] = pathinfo($pdf_file[0]);

                    $preview_url = \PluginEngine::getURL('meetingplugin', [], "api/default_slide/template/preview/$page");
                    if (isset($_SERVER['SERVER_NAME']) && strpos($preview_url, $_SERVER['SERVER_NAME']) === FALSE) {
                        $preview_url = $base_url . $preview_url;
                    }

                    $templates[$page]['pdf']['preview'] = $preview_url;

                }
                $php_file = glob("$pages_directory/$page/*.php");
                if (!empty($php_file)) {
                    $templates[$page]['php'] = pathinfo($php_file[0]);
                }
            }
        }

        return $templates;
    }

    /**
    * Uploads and stores the font file
    *
    * @param UploadedFile $font_file slim uploadedFile object of font file
    * @return boolean
    */
    public function uploadFont(UploadedFile $font_file, $type)
    {
        if (!$this->doubleCheckDirectories('font')) {
            return false;
        }

        $font_type_path = rtrim($this->font_dir, '/') . '/' . $type;
        if ($font_file->getError() === UPLOAD_ERR_OK && pathinfo($font_file->getClientFilename(), PATHINFO_EXTENSION) == 'ttf') {
            $filename = $this->moveUploadedFile($font_type_path, $font_file);
            // Clear other prev. fonts!
            foreach (glob("$font_type_path/*") as $file) {
                if (basename($file) != $filename){
                    unlink($file);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Moves the uploaded file to the upload directory and assigns it a unique name
     * to avoid overwriting an existing uploaded file.
     *
     * @param string $directory directory to which the file is moved
     * @param UploadedFile $uploadedFile file uploaded file to move
     * @return string filename of moved file
     */
    private function moveUploadedFile($directory, UploadedFile $uploadedFile)
    {
        $filename = $uploadedFile->getClientFilename();

        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

        return $filename;
    }

    /**
     * Deletes the uploaded font simply by removing the file from the folder.
     *
     * @param string $font_type font type to delete!
     * @return boolean
     */
    public function deleteFont($font_type)
    {
        if (!$this->doubleCheckDirectories('font')) {
            return false;
        }

        $font_type_path = rtrim($this->font_dir, '/') . '/' . $font_type;
        foreach (glob("$font_type_path/*") as $file ) {
            // Clear all font files.
            $this->removeFile($file);
        }
        return count(glob("$font_type_path/*")) ? false : true;
    }

    /**
     * Removes a file
     *
     * @param string $file_path the full file path
     */
    private function removeFile($file_path) {
        if (!empty($file_path) && file_exists($file_path)) {
            unlink($file_path);
        }
    }

    /**
     * Uploads and stores the file into a page directory.
     *
     * @param array $uploaded_files an array containing UploadedFile object
     * @param int $page the page number folder to upload the file to.
     * @return boolean
     */
    public function uploadTemplate($uploaded_files, $page)
    {
        $uploaded = false;
        if (isset($uploaded_files['php'])) {
            $uploaded = $this->uploadPHPTemplate($uploaded_files['php'], $page);
        }
        if (isset($uploaded_files['pdf'])) {
            $uploaded = $this->uploadPDFSlide($uploaded_files['pdf'], $page);
        }
        return $uploaded;
    }

    /**
     * Stores the php file into a template page directory.
     *
     * @param UploadedFile $php_file the php file
     * @param int $page the folder as page number
     *
     * @return boolean
     */
    private function uploadPHPTemplate(UploadedFile $php_file, $page)
    {
        if (!$this->doubleCheckDirectories('page')) {
            return false;
        }
        $pages_directory = rtrim($this->pages_dir, '/');
        $target_page_dir = "$pages_directory/$page";
        if (is_dir($target_page_dir) && $php_file->getError() === UPLOAD_ERR_OK && pathinfo($php_file->getClientFilename(), PATHINFO_EXTENSION) == 'php') {
            $filename = $this->moveUploadedFile($target_page_dir, $php_file);
            // Clear other php files.
            foreach (glob("$target_page_dir/*.php") as $file ) {
                if (basename($file) != $filename){
                    unlink($file);
                }
            }
            // Encode the content of php file
            $content = file_get_contents($target_page_dir . '/' . $filename);
            $encode_content = base64_encode($content);
            file_put_contents($target_page_dir . '/' . $filename, $encode_content);

            return true;
        }
        return false;
    }

    /**
     * Stores the pdf file into a template page directory.
     *
     * @param UploadedFile $pdf_file the php file
     * @param int $page the folder as page number
     *
     * @return boolean
     */
    private function uploadPDFSlide(UploadedFile $pdf_file, $page)
    {
        if (!$this->doubleCheckDirectories('page')) {
            return false;
        }

        $pages_directory = rtrim($this->pages_dir, '/');
        $target_page_dir = "$pages_directory/$page";
        // We create the page dir if not exists!
        if (!is_dir($target_page_dir)) {
            @mkdir($target_page_dir, 0750);
        }
        if (is_dir($target_page_dir) && $pdf_file->getError() === UPLOAD_ERR_OK && pathinfo($pdf_file->getClientFilename(), PATHINFO_EXTENSION) == 'pdf') {
            $filename = $this->moveUploadedFile($target_page_dir, $pdf_file);
            // Clear other php files.
            foreach (glob("$target_page_dir/*.pdf") as $file ) {
                if (basename($file) != $filename){
                    unlink($file);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Deletes a specific file based on the arg. inside the page directory.
     *
     * @param int $page the page folder number
     * @param int $what the type of file to delete
     *
     * @return boolean
     */
    public function deleteTemplate($page, $what)
    {
        if (!$this->doubleCheckDirectories('page')) {
            return false;
        }

        $pages_directory = rtrim($this->pages_dir, '/');
        if (empty($page) || empty($what) || ($what != 'pdf' && $what != 'php')) {
            return false;
        }

        if (is_dir("$pages_directory/$page")) {
            // By now we assume removing the pdf as removing the whole template.
            if ($what == 'pdf') {
                // removing everything inside that dir.
                foreach (glob("$pages_directory/$page/*.*") as $file) {
                    unlink($file);
                }
                // Remove the page dir.
                if (rmdir("$pages_directory/$page")) {
                    // Rearrange the whole dir based on the page numbers!
                    $scanned_pages_directory = scandir("$pages_directory");
                    foreach ($scanned_pages_directory as $scanned_page) {
                        if ($scanned_page == '.' || $scanned_page == '..') {
                            continue;
                        }
                        if ($scanned_page > $page) {
                            rename ("$pages_directory/$scanned_page", "$pages_directory/" . (intval($scanned_page) - 1));
                        }
                    }
                }
            } else {
                foreach (glob("$pages_directory/$page/*.$what") as $file) {
                    unlink($file);
                }
            }
        }
        return true;
    }

    /**
     * Check whether to generate default StudIP PDF template or use uploaded templates
     *
     * @return bool
     */
    public function checkCustomizedTemplates()
    {
        return empty($this->getInstalledTemplates()) ? false : true;
    }

    /**
     * It generates a dummy pdf to provide a preview for the admin to check how does the uploaded pdf and template looks like
     *
     * @param int $page the page number of the template
     *
     * @return Fpdi $pdf the generated pdf object
     */
    public function generatePDFPreview($page)
    {
        $pdf = new Fpdi();

        $pdf = $this->installFont($pdf);

        $course = new Course();
        $course->name = 'Test Course';
        $meeting = new Meeting();
        $meeting->courses[] = $course;
        $meeting->name = 'Test Meeting';
        $meeting->features = null;

        $pages = $this->getInstalledTemplates();

        if (isset($pages[$page])) {
            $page = $pages[$page];
            $pdf_path = "{$page['pdf']['dirname']}/{$page['pdf']['basename']}";
            $slide_page_count = $pdf->setSourceFile($pdf_path);
            // Check if the uploaded pdf file has more than one page!
            for ($slide_page = 1; $slide_page <= $slide_page_count; $slide_page++) {
                $template_id = $pdf->importPage($slide_page);
                $size = $pdf->getTemplateSize($template_id);
                $pdf->AddPage($size['orientation'], $size);
                $pdf->useTemplate($template_id);

                // Apply php templates, if only the template exists and the it is the first page of uploaded pdf!
                if (isset($page['php']) && file_exists("{$page['php']['dirname']}/{$page['php']['basename']}") && $slide_page == 1) {
                    $template_html = $this->getFlexiTemplateHTML($page['php']['dirname'], $page['php']['basename'], $meeting, true);
                    if (empty($template_html)) {
                        continue;
                    }

                    $pdf->writeHTML($template_html);
                }
            }

            return $pdf;
        }

        return false;
    }

    /**
     * Reads the content of available sample file and returns it to be downloaded by client.
     *
     * @param string $what the sample file extension to download
     *
     * @return string|boolean $content or false
     */
    public static function downloadSampleTemplate($what) {
        if ($what != 'php' && $what != 'pdf') {
            return false;
        }
        $extension = $what == 'php' ? '.txt' : '.pdf';
        $file_path = rtrim(SAMPLES_DIR, '/') . '/' . 'samples' . $extension;
        if (!file_exists($file_path)) {
            return false;
        }

        $content = file_get_contents($file_path);
        return $content ? $content : false;
    }
}
