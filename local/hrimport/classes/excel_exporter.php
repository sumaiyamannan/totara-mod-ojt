<?php

/**
 * File description
 *
 * @package
 * @subpackage
 * @copyright  &copy; 2016 Kineo Pacific {@link http://kineo.com.au}
 * @author     tri.le
 * @version    1.0
 */

require_once("$CFG->libdir/excellib.class.php");
class table_excel2_export_format extends table_excel_export_format {
    public function define_workbook() {
        global $CFG;
        // Creating a workbook
        $this->workbook = new Advanced_MoodleExcelWorkbook("-");
    }
    public function finish_document() {
        $this->workbook->close();
    }
}

class Advanced_MoodleExcelWorkbook extends MoodleExcelWorkbook {

    public function add_worksheet($name = '') {
        // Create the Moodle Worksheet. Returns one pointer to it
        $ws = new Advanced_MoodleExcelWorksheet($name, $this->objPHPExcel);
        return $ws;
    }

}

class Advanced_MoodleExcelWorksheet extends MoodleExcelWorksheet {

    private static $backgroundcolorfunc = null;

    var $workbook;
    var $colorindex = array();

    public function write_number($row, $col, $num, $format = null) {
        $this->apply_cell_format($row, $col);
        parent::write_number($row, $col, $num, $format);
    }

    public function write_string($row, $col, $str, $format = null) {
        $this->apply_cell_format($row, $col);
        parent::write_string($row, $col, $str, $format);
    }

    private function set_cell_background($row, $col, $color) {
        $this->worksheet->getStyleByColumnAndRow($col, $row)->applyFromArray(array(
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color'=> array('rgb' => $color),
            )
        ));
    }

    private function apply_cell_format($row, $col) {
        $bkcolor = call_user_func(self::$backgroundcolorfunc, $row, $col);
        if ($bkcolor) {
            $this->set_cell_background($row, $col, $bkcolor);
        }
    }

    static function set_background_color_function($callable) {
        self::$backgroundcolorfunc = $callable;
    }
}


