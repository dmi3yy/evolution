<?php
namespace EvolutionCMS\Support;

use EvolutionCMS\Interfaces\DataGridInterface;
use Illuminate\Database\Query\Builder;
use IntlDateFormatter;

#
# DataGrid Class
# Created By Raymond Irving 15-Feb,2004
# Based on CLASP 2.0 (www.claspdev.com)
# -----------------------------------------
# Licensed under the LGPL
# -----------------------------------------
#

class DataGrid implements DataGridInterface
{

    public $ds; // datasource
    public $id;
    public $pageSize;            // pager settings
    public $pageNumber;
    public $pager;
    public $pagerLocation;        // top-right, top-left, bottom-left, bottom-right, both-left, both-right

    public $cssStyle;
    public $cssClass;

    public $columnHeaderStyle = '';
    public $columnHeaderClass = '';
    public $itemStyle = '';
    public $itemClass = '';
    public $altItemStyle;
    public $altItemClass;

    public $fields;
    public $columns;
    public $colWidths = '';
    public $colAligns = '';
    public $colWraps = '';
    public $colColors = '';
    public $colTypes = '';            // coltype1, coltype2, etc or coltype1:format1, e.g. date:%Y %m
    // data type: integer,float,currency,date

    public $header;
    public $footer;
    public $cellPadding;
    public $cellSpacing;

    public $rowAlign = '';            // vertical alignment: top, middle, bottom
    public $rowIdField = '';

    public $pagerStyle;
    public $pagerClass;
    public $pageClass;
    public $selPageClass;
    public $noRecordMsg = "No records found.";

    public $_itemStyle;
    public $_itemClass;
    public $_altItemClass;
    public $_altItemStyle;
    public $_isDataset;
    public $_colcount;
    public $_colnames;
    public $_colwidths;
    public $_colaligns;
    public $_colcolors;
    public $_coltypes;
    public $_colwraps;
    public $_alt;
    public $_total;
    public $_fieldnames;
    public $prepareResult = [];
    /**
     * @see datagrid modifier in manager/includes/extenders/modifiers.class.inc.php
     */
    public $cdelim;

    public static $dataGridCnt;

    public function __construct($id, $ds, $pageSize = 20, $pageNumber = -1)
    {
        // set id
        self::$dataGridCnt++;
        $this->id = $this->id ? empty($id) : "dg" . self::$dataGridCnt;

        // set datasource
        $this->ds = $ds;

        // set pager
        $this->pageSize = $pageSize;
        $this->pageNumber = $pageNumber; // by setting pager to -1 will cause pager to load it's last page number
        $this->pagerLocation = 'top-right';
    }

    public function setDataSource($ds)
    {
        $this->ds = $ds;
    }

    public function render()
    {
        $modx = evolutionCMS();
        $columnHeaderStyle = ($this->columnHeaderStyle) ? "style='" . $this->columnHeaderStyle . "'" : '';
        $columnHeaderClass = ($this->columnHeaderClass) ? "class='" . $this->columnHeaderClass . "'" : "";
        $cssStyle = ($this->cssStyle) ? "style='" . $this->cssStyle . "'" : '';
        $cssClass = ($this->cssClass) ? "class='" . $this->cssClass . "'" : '';

        $pagerClass = ($this->pagerClass) ? "class='" . $this->pagerClass . "'" : '';
        $pagerStyle = ($this->pagerStyle) ? "style='" . $this->pagerStyle . "'" : "style='background-color:#ffffff;'";

        $this->_itemStyle = ($this->itemStyle) ? "style='" . $this->itemStyle . "'" : '';
        $this->_itemClass = ($this->itemClass) ? "class='" . $this->itemClass . "'" : '';
        $this->_altItemStyle = ($this->altItemStyle) ? "style='" . $this->altItemStyle . "'" : '';
        $this->_altItemClass = ($this->altItemClass) ? "class='" . $this->altItemClass . "'" : '';

        $this->_alt = 0;
        $this->_total = 0;

        $this->_isDataset = $this->ds instanceof \Illuminate\Database\Eloquent\Builder; // if not dataset then treat as array

        if (!$cssStyle && !$cssClass) {
            $cssStyle = "style='width:100%;border:1px solid silver;font-family:verdana,arial; font-size:11px;'";
        }
        if (!$columnHeaderStyle && !$columnHeaderClass) {
            $columnHeaderStyle = "style='color:black;background-color:silver'";
        }
        if (!$this->_itemStyle && !$this->_itemClass) {
            $this->_itemStyle = "style='color:black;'";
        }
        if (!$this->_altItemStyle && !$this->_altItemClass) {
            $this->_altItemStyle = "style='color:black;background-color:#eeeeee'";
        }

        if ($this->_isDataset && !$this->columns) {
            $first = $this->ds->first()->toArray();
            $this->columns = implode(',', array_keys($first));
        }

        // start grid
        $tblStart = "<table $cssClass $cssStyle cellpadding='" . (isset($this->cellPadding) ? (int)$this->cellPadding : 1) . "' cellspacing='" . (isset($this->cellSpacing) ? (int)$this->cellSpacing : 1) . "'>";
        $tblEnd = "</table>";

        // build column header
        $this->_colnames = explode((strstr($this->columns ?? '', "||") !== false ? "||" : ","), $this->columns ?? '');
        $this->_colwidths = explode((strstr($this->colWidths ?? '', "||") !== false ? "||" : ","), $this->colWidths ?? '');
        $this->_colaligns = explode((strstr($this->colAligns ?? '', "||") !== false ? "||" : ","), $this->colAligns ?? '');
        $this->_colwraps = explode((strstr($this->colWraps ?? '', "||") !== false ? "||" : ","), $this->colWraps ?? '');
        $this->_colcolors = explode((strstr($this->colColors ?? '', "||") !== false ? "||" : ","), $this->colColors ?? '');
        $this->_coltypes = explode((strstr($this->colTypes ?? '', "||") !== false ? "||" : ","), $this->colTypes ?? '');
        $this->_colcount = count($this->_colnames);
        if (!$this->_isDataset) {
            $this->ds = explode((strstr($this->ds ?? '', "||") !== false ? "||" : ","), $this->ds ?? '');
            $this->ds = array_chunk($this->ds, $this->_colcount);
        }
        $tblColHdr = "<thead><tr>";
        for ($c = 0; $c < $this->_colcount; $c++) {
            $name = $this->_colnames[$c];
            $width = $this->_colwidths[$c] ?? null;
            $tblColHdr .= "<td $columnHeaderStyle $columnHeaderClass" . ($width ? " width='$width'" : "") . ">$name</td>";
        }
        $tblColHdr .= "</tr></thead>\n";

        // build rows
        $rowcount = $this->_isDataset ? $this->ds->count() : count($this->ds);
        $tblRows = '';
        $this->_fieldnames = explode(",", $this->fields);
        if ($rowcount == 0) {
            $tblRows .= "<tr><td " . $this->_itemStyle . " " . $this->_itemClass . " colspan='" . $this->_colcount . "'>" . $this->noRecordMsg . "</td></tr>\n";
        } else {
            // render grid items
            if ($this->pageSize <= 0) {
                if ($this->_isDataset) {
                    $r = 0;
                    foreach ($this->ds->get()->toArray() as $row) {
                        $tblRows .= $this->RenderRowFnc(++$r, $row);
                    }
                } else {
                    for ($r = 0; $r < $rowcount; $r++) {
                        $row = $this->ds[$r];
                        $tblRows .= $this->RenderRowFnc($r + 1, $row);
                    }
                }
            } else {
                if (!$this->pager) {
                    $this->pager = new DataSetPager(
                        $this->id,
                        $this->ds,
                        $this->pageSize,
                        $this->pageNumber,
                        $this->prepareResult
                    );
                    $this->pager->setRenderRowFnc($this); // pass this object
                    $this->pager->cssStyle = $pagerStyle;
                    $this->pager->cssClass = $pagerClass;
                } else {
                    $this->pager->pageSize = $this->pageSize;
                    $this->pager->pageNumber = $this->pageNumber;
                }

                $this->pager->render();
                $tblRows = $this->pager->getRenderedRows();
                $tblPager = $this->pager->getRenderedPager();
            }
        }

        // setup header,pager and footer
        $o = $tblStart;
        $ptop = (substr($this->pagerLocation, 0, 3) == "top") || (substr($this->pagerLocation, 0, 4) == "both");
        $pbot = (substr($this->pagerLocation, 0, 3) == "bot") || (substr($this->pagerLocation, 0, 4) == "both");
        if ($this->header) {
            $o .= "<tr><td bgcolor='#ffffff' colspan='" . $this->_colcount . "'>" . $this->header . "</td></tr>";
        }
        if (!empty($tblPager) && $ptop) {
            $o .= "<tr><td align='" . (substr(
                    $this->pagerLocation,
                    -4
                ) == "left" ? "left" : "right") . "' $pagerClass $pagerStyle colspan='" . $this->_colcount . "'>" . $tblPager . "&nbsp;</td></tr>";
        }
        $o .= $tblColHdr . $tblRows;
        if (!empty($tblPager) && $pbot) {
            $o .= "<tr><td align='" . (substr(
                    $this->pagerLocation,
                    -4
                ) == "left" ? "left" : "right") . "' $pagerClass $pagerStyle colspan='" . $this->_colcount . "'>" . $tblPager . "&nbsp;</td></tr>";
        }
        if ($this->footer) {
            $o .= "<tr><td bgcolor='#ffffff' colspan='" . $this->_colcount . "'>" . $this->footer . "</td></tr>";
        }
        $o .= $tblEnd;

        return $o;
    }

    // format column values

    public function RenderRowFnc($n, $row)
    {
        foreach ($this->prepareResult as $key => $value) {
            if (isset($row[$key]) && isset($value[$row[$key]])) {
                $row[$key] = $value[$row[$key]];
            }
            if ($row[$key] === '' && isset($value['__'])) {
                $row[$key] = $value['__'];
            }
            if (isset($value['__checktime'])) {
                $row[$key] = $this->checkTime($value['__checktime'], $row);
            }
        }
        if ($this->_alt == 0) {
            $Style = $this->_itemStyle;
            $Class = $this->_itemClass;
            $this->_alt = 1;
        } else {
            $Style = $this->_altItemStyle;
            $Class = $this->_altItemClass;
            $this->_alt = 0;
        }
        $o = "<tr>";
        for ($c = 0; $c < $this->_colcount; $c++) {
            $colStyle = $Style;
            $fld = trim($this->_fieldnames[$c]);
            $width = isset($this->_colwidths[$c]) ? $this->_colwidths[$c] : '';
            $align = isset($this->_colaligns[$c]) ? $this->_colaligns[$c] : '';
            $color = isset($this->_colcolors[$c]) ? $this->_colcolors[$c] : '';
            $type = isset($this->_coltypes[$c]) ? $this->_coltypes[$c] : '';
            $nowrap = isset($this->_colwraps[$c]) ? $this->_colwraps[$c] : '';
            $value = get_by_key($row, ($this->_isDataset && $fld ? $fld : $c), '');
            if ($color && $Style) {
                $colStyle = substr($colStyle, 0, -1) . ";background-color:$color;'";
            }
            $value = $this->formatColumnValue($row, $value, $type, $align);
            $o .= "<td $colStyle $Class" . ($align ? " align='$align'" : "") . ($color ? " bgcolor='$color'" : "") . ($nowrap ? " nowrap='$nowrap'" : "") . ($width ? " width='$width'" : "") . ">$value</td>";
        }
        $o .= "</tr>\n";

        return $o;
    }

    public function formatColumnValue($row, $value, $type, &$align)
    {
        if (strpos($type ?? '', ":") !== false) {
            list($type, $type_format) = explode(":", $type, 2);
        }
        switch (strtolower($type ?? '')) {
            case "integer":
                if ($align == "") {
                    $align = "right";
                }
                $value = number_format($value);
                break;

            case "float":
                if ($align == "") {
                    $align = "right";
                }
                if (!$type_format) {
                    $type_format = 2;
                }
                $value = number_format($value, $type_format);
                break;

            case "currency":
                if ($align == "") {
                    $align = "right";
                }
                if (!$type_format) {
                    $type_format = 2;
                }
                $value = "$" . number_format($value, $type_format);
                break;

            case "date":
                if ($align == "") {
                    $align = "right";
                }
                if (!is_numeric($value)) {
                    $value = strtotime($value);
                }

                $value = EvolutionCMS()->toDateFormat($value);
                break;

            case "boolean":
                if ($align == '') {
                    $align = "center";
                }
                $value = number_format($value);
                if ($value) {
                    $value = '&bull;';
                } else {
                    $value = '&nbsp;';
                }
                break;

            case "template":
                // replace [+value+] first
                $value = str_replace("[+value+]", $value, $type_format);
                // replace other [+fields+]
                if (strpos($value, "[+") !== false) {
                    foreach ($row as $k => $v) {
                        $value = str_replace("[+$k+]", $v ?? '', $value);
                    }
                }
                break;
        }

        return $value;
    }

    private function checkTime($__checktime, $row)
    {
        $value = '';
        foreach ($__checktime as $checkTime) {
            if ($row[$checkTime] != 0) {
                switch ($checkTime) {
                    case 'blockedafter':
                        if ($row[$checkTime] < time()) {
                            $value = __('global.yes');
                        }
                        break;
                    case 'blockeduntil':
                        if ($row[$checkTime] > time()) {
                            $value = __('global.yes');
                        }
                        break;
                }
            }
        }

        return $value;
    }
}
