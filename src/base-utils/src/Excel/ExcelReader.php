<?php
namespace HyperfAdmin\BaseUtils\Excel;

use Box\Spout\Common\Type;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

class ExcelReader
{
    private $sheets;

    public function __construct($path)
    {
        $extension = get_extension($path);
        switch($extension) {
            case Type::CSV:
                /** @var \Box\Spout\Reader\CSV\Reader $reader */ $reader = ReaderEntityFactory::createCSVReader();
                break;
            case Type::XLSX:
                /** @var \Box\Spout\Reader\XLSX\Reader $reader */ $reader = ReaderEntityFactory::createXLSXReader();
                break;
            case Type::ODS:
                /** @var \Box\Spout\Reader\ODS\Reader $reader */ $reader = ReaderEntityFactory::createODSReader();
                break;
        }
        $reader->open($path);
        $iterator = $reader->getSheetIterator();
        /** @var \Box\Spout\Reader\XLSX\Sheet $sheet */
        foreach($iterator as $sheet) {
            $this->sheets[] = $sheet;
        }
    }

    public function readLine($sheet_index = 0)
    {
        $sheet = $this->sheets[$sheet_index] ?? null;
        if(!$sheet) {
            return false;
        }
        foreach($sheet->getRowIterator() as $row) {
            $cells = $row->getCells();
            $row = [];
            /** @var \Box\Spout\Common\Entity\Cell $cell */
            foreach($cells as $cell) {
                $row[] = $cell->getValue();
            }
            yield $row;
        }
    }
}
