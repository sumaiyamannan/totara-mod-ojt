<?php // Catalyst cronlog.php - shows the cron log for the current server

require_once("../config.php");
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('cronlog');

$readmax = optional_param('lines', 5000, PARAM_INT);  // The number of lines to read and show

echo $OUTPUT->header();

echo $OUTPUT->heading('cronlog');

$filename = "{$CFG->dataroot}/cron.log";
//$filename = escapeshellarg($file);      // Just to be safe, if this script gets extended in future ;)
if (!file_exists($filename)) {
    print_error('Cron log file not found');
}
if (!$file = fopen($filename, 'r')) {
    print_error('Cron log file not found');
}

$offset = filesize($filename) - 1;
fseek($file, $offset--); //seek to the end of the file

$readcount = 0;
// Set the filepointer, to only read the end part of the file
while($readcount <= $readmax && $offset > 0) {
    if (fgetc($file) == PHP_EOL) {
        $readcount++;
    }
    fseek($file, $offset--);
}
fseek($file, $offset+2); // move filepointer onto next line

// Now, read from the file pointer, onwards
echo '<div class="cronlog">';
echo '<form method="GET" name="frmCronlog">';
echo get_string('lines', 'admin').'<input type="text" name="lines" value="'.$readmax.'" size="5" />';
echo '<input type="submit" value="Go" />';
echo '</form>';
echo '<p><strong>'.get_string('cronlogshowinglastlines', 'admin', $readmax).'</strong></p>';
echo '<pre>';

while ($line = fgets($file)) {
    echo $line;
}
echo '</pre>';
echo '</div>';

fclose($file);

echo $OUTPUT->footer();