<?php
namespace HyperfAdmin\BaseUtils\Excel;

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\Common\Creator\WriterFactory;

class ExcelWriter
{
    private $writer;

    public function __construct($path)
    {
        $extension = get_extension($path);
        $this->writer = WriterFactory::createFromType($extension);
        $this->writer = $this->writer->openToFile($path);
    }

    public function addRows($rows)
    {
        $rows = WriterEntityFactory::createRowFromArray($rows);
        $this->writer->addRow($rows);
    }

    public function close()
    {
        $this->writer->close();;
    }
}
