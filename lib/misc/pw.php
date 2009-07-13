<?php
/* _create_pw()
 *
 * Creates a random password using the base specified below.
 * Numbers 0 and 1 have been removed because they can be mistaken for 'O' and 'l'
 *
 * For change of characters allowed in password, edit $base
 *
 * Author: Henrik Austad <henrik.austad@uninett.no>
 */
function create_pw($length) {
    $base ="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ23456789";
    $base_length = strlen($base);
    $gen_pw = "";
    $counter = 0;
    // loop for $length rounds and append a character to the result
    // also, it's no longer necessary to seed rand() with srand().
    while ($counter < $length) {
        $index = rand(0, $base_length);
        $gen_pw = $gen_pw . substr($base, $index, 1);
        $counter++;
    }
    return $gen_pw;
} // end _create_pw

/**
 * scramble_password - make sure a password is stored safely in the database.
 *
 * Change of this function will *not* affect the use of the system as this is
 * used by all parts.
 *
 * Change this to md5sum, or some other hash-function (it *must* be
 * deterministic!). This will not lead to an unfunctional system. just remember
 * to expand the space in the database if the hash is longer than 40 characters.
 */
function scramble_passwd($pw)
{
    return sha1($pw);
}


?>
