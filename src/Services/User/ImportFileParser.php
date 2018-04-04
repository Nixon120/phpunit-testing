<?php

namespace Services\User;

use Entities\User;

class ImportFileParser
{
    private $inputFilePath;
    private $users = [];

    public function __construct($inputFilePath)
    {
        $this->inputFilePath = $inputFilePath;
    }

    public function getUsers()
    {
        $objPHPExcel = \PHPExcel_IOFactory::load($this->inputFilePath);

        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        for ($row = 1; $row <= $highestRow; $row++) {
            //  Read a row of data into an array
            $rowData = $sheet->rangeToArray(
                'A' . $row . ':' . $highestColumn . $row,
                null,
                true,
                false
            );

            foreach ($rowData as $singleRow) {
                if ($singleRow[0] != 'Email') {
                    $this->users[] = $this->createUserFromRow($singleRow);
                }
            }
        }

        return $this->users;
    }

    private function createUserFromRow($row)
    {
        return new User(
            [
                'email_address' => $row[0]
            ]
        );
    }
}
