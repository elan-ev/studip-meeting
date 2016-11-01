<?php
/**
 * Helper.php - helper functions
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 */

namespace ElanEv\Model;

class Helper
{
    public static function createPassword()
    {
        do {
            $bytes = openssl_random_pseudo_bytes(32, $strong);

            if (false !== $bytes && true === $strong) {
                $pw = substr(base64_encode($bytes), 0, 20);
            } else {
                throw new \Exception("Unable to generate secure token from OpenSSL.");
            }

        // make sure that at least one of each of the following chars is present:
        // uppercase letter, lowercase letter, number, symbol
        } while (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*(_|[^\w])).+$/', $pw));

        return $pw;
    }
}
