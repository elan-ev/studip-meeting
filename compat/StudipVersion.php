<?php
/**
 * @author Till Glöggler <tgloeggl@uos.de>
 */
if (!class_exists('StudipVersion')) {
    class StudipVersion
    {
        /**
         * Returns the current Stud.IP-version
         *
         * @param bool $major  if true (default) only the major version part is returned
         * @return string
         */
        private static function getStudipVersion($major)
        {
            if ($major) {
                return substr($GLOBALS['SOFTWARE_VERSION'], 0, 3);
            } else {
                return $GLOBALS['SOFTWARE_VERSION'];
            }
        }

        /**
         * Returns true if passed version is newer than the current Stud.IP version
         *
         * @param string $version
         * @param bool $major  if true (default) only the major version part is checked (fe. 3.4, 3.5, ...)
         * @return bool
         */
        public static function newerThan($version, $major = true)
        {
            return (version_compare(self::getStudipVersion($major), $version, '>'));
        }

        /**
         * Returns true if passed version is older than the current Stud.IP version
         *
         * @param string $version
         * @param bool $major  if true (default) only the major version part is checked (fe. 3.4, 3.5, ...)
         * @return bool
         */
        public static function olderThan($version, $major = true)
        {
            return (version_compare(self::getStudipVersion($major), $version, '<'));
        }

        /**
         * Returns true if passed version matches the current Stud.IP version
         *
         * @param string $version
         * @param bool $major  if true (default) only the major version part is checked (fe. 3.4, 3.5, ...)
         * @return bool
         */
        public static function matches($version, $major = true)
        {
            return (version_compare(self::getStudipVersion($major), $version, '='));
        }

        /**
         * Returns true if version equals or is between the two passed versions
         *
         * @param string $from_version
         * @param string $to_version
         * @param bool $major  if true (default) only the major version part is checked (fe. 3.4, 3.5, ...)
         *
         * @return bool
         */
        public static function range($from_version, $to_version, $major = true)
        {
            return version_compare(self::getStudipVersion($major), $from_version, '>=')
                    && version_compare(self::getStudipVersion($major), $to_version, '<=');
        }
    }
}
