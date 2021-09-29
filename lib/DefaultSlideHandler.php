<?php

namespace Meetings;
use Meetings\Models\I18N;
use ElanEv\Model\Meeting;

use Course;
use StudipNews;
use Slim\Http\UploadedFile;
use TCPDF_FONTS;
use TCPDF;
use Flexi_TemplateFactory;
use ExportPDF;
use setasign\Fpdi\Tcpdf\Fpdi;
use Meetings\Errors\Error;
/**
 * DefaultSlideHandler.php - contains function to handle news & announcements to show as default slide,
 * as well as all the default slide functions to manage by admin.
 *
 * @author Farbod Zamani Broujeni (zamani@elan-ev.de)
 */

define(DEFAULT_SLIDE_TEMPLATE_DIR, dirname(__DIR__, 1) . '/templates/default_slides');
define(TEMPLATES_DIR, dirname(__DIR__, 1) . '/templates');
class DefaultSlideHandler
{
    static protected $font_dir = DEFAULT_SLIDE_TEMPLATE_DIR . '/font/';
    static protected $pages_dir = DEFAULT_SLIDE_TEMPLATE_DIR . '/page/';
    static protected $samples_dir = DEFAULT_SLIDE_TEMPLATE_DIR . '/samples/';

    /**
     * Get all news from course and studip
     * 
     * @param string $courseid the meeting object
     * @param int $limit the number of news to show, default is 3.
     * @return array
     */
    public static function getNewsList($range = 'studip', $limit = 3)
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
     * @return array
     */
    public static function getIntroTexts(Meeting $meeting)
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
     * Check whether to generate default StudIP PDF template or use uploaded templates
     * 
     * @return bool
     */
    public static function checkCustomizedTemplates()
    {
        return empty(self::getInstalledTemplates()) ? false : true;
    }

    /**
    * It generates a dummy pdf to provide a preview for the admin to check how does the uploaded pdf and template looks like
    *
    * @param int $page the page number of the template
    * @return Fpdi $pdf the generated pdf object
    */
    public static function generatePDFPreview($page)
    {
        $pdf = new Fpdi();

        $pdf = self::installFont($pdf);

        $course = new Course();
        $course->name = 'Test Course';
        $meeting = new Meeting();
        $meeting->courses[] = $course;
        $meeting->name = 'Test Meeting'; 
        $meeting->features = null;

        $pages = self::getInstalledTemplates();

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
                    $php_path = "{$page['php']['dirname']}/{$page['php']['basename']}";
                    $template = self::getFlexiTemplate($page['php']['dirname'], $page['php']['basename'], $meeting);
                    if (!$template) {
                        continue;
                    }
                    
                    $pdf->writeHTML($template->render());
                }
            }

            return $pdf;
        }

        return false;
    }

    /**
    * It generates the pdf from the uploaded slides and templates by admin
    *
    * @param Meeting $meeting the meeting object
    * @param string $courseid the course id
    * @return Fpdi $pdf the generated pdf object
    */
    public static function generateCustomizedPDF(Meeting $meeting, $courseid)
    {
        $pdf = new Fpdi();

        $pdf = self::installFont($pdf);

        $pages = self::getInstalledTemplates();
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
                        $php_path = "{$page['php']['dirname']}/{$page['php']['basename']}";
                        $template = self::getFlexiTemplate($page['php']['dirname'], $page['php']['basename'], $meeting);
                        if (!$template) {
                            continue;
                        }

                        $pdf->writeHTML($template->render());
                    }
                }
            }
        }

        return $pdf;
    }

    /**
    * It generates the Flexi_Template object and sets attributes based on configs
    *
    * @param string $template_factory_dir the directory path of templates
    * @param string $template_name the name of the template to use
    * @param Meeting $meeting the meeting object
    * @return Flexi_TemplateFactory $template generated template
    */
    private static function getFlexiTemplate($template_factory_dir, $template_name, Meeting $meeting)
    {
        $template_factory = new Flexi_TemplateFactory($template_factory_dir);
        $template = $template_factory->open($template_name);
        if (!$template) {
            return false;
        }

        $template->set_attribute('texts', self::getIntroTexts($meeting));
        $features = json_decode($meeting->features, true);
        $show_course_news = $features && isset($features['default_slide_course_news']) ? filter_var($features['default_slide_course_news'], FILTER_VALIDATE_BOOLEAN) : false;
        $show_studip_news = $features && isset($features['default_slide_studip_news']) ? filter_var($features['default_slide_studip_news'], FILTER_VALIDATE_BOOLEAN) : false;
        
        $course_news = self::getNewsList($courseid);
        if ($show_course_news && !empty($course_news)) {
            $template->set_attribute('course_news', $course_news);
        }

        $studip_news = self::getNewsList();
        if ($show_studip_news && !empty($studip_news)) {
            $template->set_attribute('studip_news', $studip_news);
        }

        return $template;
    }

    /**
    * It generates the default StudIP supported PDF and renders the template in it
    *
    * @param Meeting $meeting the meeting object
    * @param string $courseid the course id
    * @return ExportPDF $pdf the generated TCPDF pdf object
    */
    public static function generateStudIPDefaultPDF(Meeting $meeting, $courseid)
    {
        $template = self::getFlexiTemplate(TEMPLATES_DIR, 'default_slide.php', $meeting);
        if (!$template) {
            return;
        }

        $pdf = new ExportPDF();

        $pdf = self::installFont($pdf);

        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        $pdf->addPage('L', '', true, false);
        $pdf->SetFont($font_name, '', ($decrease_font_size) ? 12 : 18);
        $pdf->writeHTML($template->render(), true, false, true, false, '');

        $pdf->lastPage();

        // Adding a blank page as a white board!
        $pdf->addPage('L', '', false, false);

        return $pdf;
    }

    /**
    * It installs the font using TCPDF_FONTS class which generates a font php file to be used by TCPDF in AddFont class.
    *
    * @param TCPDF $pdf the generated pdf object
    * @return TCPDF $pdf
    */
    public static function installFont(TCPDF $pdf)
    {
        $font_size = 16;

        $installed_font_name = defined('PDF_FONT_NAME_MAIN') ? PDF_FONT_NAME_MAIN : 'helvetica';

        // Regular Font
        $regular_font_file = self::installFontFileByType('regular');
        // Regular font is required!
        if (empty($regular_font_file)) {
            $pdf->SetFont($installed_font_name, '', $font_size);
            return $pdf;
        }
        $installed_font_name = pathinfo($regular_font_file, PATHINFO_FILENAME);

        $pdf->AddFont($installed_font_name, '', $regular_font_file);

        // Bold Font.
        $bold_font_file = self::installFontFileByType('bold');
        if (empty($bold_font_file)) {
            $bold_font_file = $regular_font_file;
        }
        $pdf->AddFont($installed_font_name, 'B', $bold_font_file);

        // Italic Font.
        $italic_font_file = self::installFontFileByType('italic');
        if (empty($italic_font_file)) {
            $italic_font_file = $regular_font_file;
        }
        $pdf->AddFont($installed_font_name, 'I', $bold_font_file);

        // Bold Italic Font
        $bolditalic_font_file = self::installFontFileByType('bold_italic');
        if (empty($bolditalic_font_file)) {
            $bolditalic_font_file = $regular_font_file;
        }
        $pdf->AddFont($installed_font_name, 'BI', $bold_font_file);

        $pdf->SetFont($installed_font_name, '', $font_size);
        return $pdf;
    }

    private static function installFontFileByType($type)
    {
        $font_type_dir_path = rtrim(self::$font_dir, '/') . "/$type";
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
    * Returns installed (uploaded) font with ttf extension
    *
    * @return array $font all types of installed font
    */
    public static function getInstalledFont()
    {
        // Take care of init folder structure.
        if (!is_dir(self::$font_dir)) {
            mkdir(rtrim(self::$font_dir, '/') . '/regular', 0777, true);
            mkdir(rtrim(self::$font_dir, '/') . '/bold');
            mkdir(rtrim(self::$font_dir, '/') . '/italic');
            mkdir(rtrim(self::$font_dir, '/') . '/bold_italic');
        }
        $font = [];
        $font_types = scandir(self::$font_dir);
        foreach ($font_types as $type) {
            if ($type == '.' || $type == '..') {
                continue;
            }
            $font_type_obj['type'] = $type;
            $font_type_path = rtrim(self::$font_dir, '/') . '/' . $type;
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
    * Uploads and stores the font file
    *
    * @param UploadedFile $font_file slim uploadedFile object of font file
    * @return boolean
    */
    public static function uploadFont(UploadedFile $font_file, $type)
    {
        $font_type_path = rtrim(self::$font_dir, '/') . '/' . $type;
        if (!is_dir($font_type_path)) {
            mkdir($font_type_path, 0777, true);
        }
        if ($font_file->getError() === UPLOAD_ERR_OK && pathinfo($font_file->getClientFilename(), PATHINFO_EXTENSION) == 'ttf') {
            $filename = self::moveUploadedFile($font_type_path, $font_file);
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
    * Deletes the uploaded font simply by removing the file from the folder.
    *
    * @param string $font_type font type to delete!
    * @return boolean
    */
    public static function deleteFont($font_type)
    {
        $font_type_path = rtrim(self::$font_dir, '/') . '/' . $font_type;
        if (!is_dir($font_type_path)) {
            mkdir($font_type_path, 0777, true);
        }
        foreach (glob("$font_type_path/*") as $file ) {
            // Clear all font files.
            unlink($file);
        }
        return count(glob("$font_type_path/*")) ? false : true;
    }

    /**
    * Reads all the available folders in pages directory are returns an array of templates based on existing folder in pages dir. 
    *
    * @return array $templates
    */
    public static function getInstalledTemplates()
    {
        // Optimizing base url.
        $base_url = sprintf(
            "%s://%s",
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['SERVER_NAME']
        );

        $pages_directory = rtrim(self::$pages_dir, '/');
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
    * Deletes a specific file based on the arg. inside the page directory.
    *
    * @param int $page the page folder number
    * @param int $what the type of file to delete
    * @return boolean
    */
    public static function deleteTemplate($page, $what)
    {
        $pages_directory = rtrim(self::$pages_dir, '/');
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
    * Uploads and stores the file into a page directory.
    *
    * @param array $uploaded_files an array containing UploadedFile object
    * @param int $page the page number folder to upload the file to.
    * @return boolean
    */
    public static function uploadTemplate($uploaded_files, $page)
    {
        $uploaded = false;
        if (isset($uploaded_files['php'])) {
            $uploaded = self::uploadPHPTemplate($uploaded_files['php'], $page);
        }
        if (isset($uploaded_files['pdf'])) {
            $uploaded = self::uploadPDFSlide($uploaded_files['pdf'], $page);
        }
        return $uploaded;
    }

    /**
    * Stores the php file into a template page directory.
    *
    * @param UploadedFile $php_file the php file
    * @param int $page the folder as page number
    * @return boolean
    */
    private static function uploadPHPTemplate(UploadedFile $php_file, $page)
    {
        $pages_directory = rtrim(self::$pages_dir, '/');
        $target_page_dir = "$pages_directory/$page";
        if (is_dir($target_page_dir) && $php_file->getError() === UPLOAD_ERR_OK && pathinfo($php_file->getClientFilename(), PATHINFO_EXTENSION) == 'php') {
            $filename = self::moveUploadedFile($target_page_dir, $php_file);
            // Clear other php files.
            foreach (glob("$target_page_dir/*.php") as $file ) {
                if (basename($file) != $filename){
                    unlink($file);
                }
            }
            return true;
        }
        return false;
    }

    /**
    * Stores the pdf file into a template page directory.
    *
    * @param UploadedFile $pdf_file the php file
    * @param int $page the folder as page number
    * @return boolean
    */
    private static function uploadPDFSlide(UploadedFile $pdf_file, $page)
    {
        $pages_directory = rtrim(self::$pages_dir, '/');
        $target_page_dir = "$pages_directory/$page";
        // We create the page dir if not exists!
        if (!is_dir($target_page_dir)) {
            mkdir($target_page_dir, 0777, true);
        }
        if (is_dir($target_page_dir) && $pdf_file->getError() === UPLOAD_ERR_OK && pathinfo($pdf_file->getClientFilename(), PATHINFO_EXTENSION) == 'pdf') {
            $filename = self::moveUploadedFile($target_page_dir, $pdf_file);
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
     * Moves the uploaded file to the upload directory and assigns it a unique name
     * to avoid overwriting an existing uploaded file.
     *
     * @param string $directory directory to which the file is moved
     * @param UploadedFile $uploadedFile file uploaded file to move
     * @return string filename of moved file
     */
    private static function moveUploadedFile($directory, UploadedFile $uploadedFile)
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $filename = $uploadedFile->getClientFilename();

        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

        return $filename;
    }

    /**
    * Reads the content of available sample file and returns it to be downloaded by client.
    *
    * @param string $what the sample file extension to download
    * @return string/boolean $content
    */
    public static function downloadSampleTemplate($what) {
        if ($what != 'php' && $what != 'pdf') {
            return false;
        }
        $file_path = rtrim(self::$samples_dir, '/') . '/' . 'samples.' . $what;
        if (!file_exists($file_path)) {
            return false;
        }

        $content = file_get_contents($file_path);
        return $content ? $content : false;
    }

}