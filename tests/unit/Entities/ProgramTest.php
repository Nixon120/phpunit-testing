<?php


use Entities\Program;
use PHPUnit\Framework\TestCase;

class ProgramTest extends TestCase
{
    /** @var  Program */
    private $program;

    /**
     * Regression test to ensure that an empty string is returned when no cost center ID is set.
     */
    public function testCostCenterReturnsEmptyStringWhenNotSet()
    {
        $program = new Program();

        $this->assertSame('',$program->getCostCenterId());
    }

    /**
     * Regression test to ensure that a 0 (default) is returned when no deposit amount is set.
     */
    public function testDepositAmountReturnsZeroWhenNotSet()
    {
        $program = new Program();

        $this->assertSame(0,$program->getDepositAmount());
    }

    public function testReturnsJsonStringOfProductsForProgramLayoutProductRowType()
    {
        $program = $this->mockProgram();

        $program->setLayoutRows($this->mockLayoutRow());

        $this->assertEquals($this->getEncodedProductRow(), $program->getLayoutRows()['cards'][0]['product_row']);
    }

    public function testReturnsLabelFromRowOfTheProgramLayout()
    {
        $program = $this->mockProgram();

        $program->setLayoutRows($this->mockLayoutRowWithLabel());

        $this->assertEquals($this->mockLayoutRowWithLabel()['label'], $program->getLayoutRows()['label']);
    }

    public function testReturnsEmptyLabelFromRowOfTheProgramLayout()
    {
        $program = $this->mockProgram();

        $program->setLayoutRows($this->mockLayoutRowWithNullLabel());

        $this->assertEquals($this->mockLayoutRowWithNullLabel()['label'], $program->getLayoutRows()['label']);
    }

    private function mockProgram()
    {
        if (!$this->program) {
            $this->program = new Program([
                'organization_id' => 1,
                'name' => 'Fake Program 1'
            ]);
        }

        return $this->program;
    }

    private function mockLayoutRow()
    {
        return [
            'program_id' => $this->program,
            'priority' => 1,
            'cards' => $this->getMockCards(),
        ];
    }

    private function getMockCards()
    {
        return [
            [
                "row_id" => 17,
                "priority" => 0,
                "size" => 9,
                "type" => "product_row",
                "image" => null,
                "product" => null,
                "product_row" => json_encode([
                    "HRA01",
                    "VVISA01",
                    "PVISA01"
                ]),
                "link" => null
            ],
            [
                "row_id" => 17,
                "priority" => 1,
                "size" => 3,
                "type" => "image",
                "image" => null,
                "product" => null,
                "product_row" => null,
                "link" => null
            ]
        ];
    }

    private function getEncodedProductRow()
    {
        return json_encode([
            "HRA01",
            "VVISA01",
            "PVISA01"
        ]);
    }

    private function mockLayoutRowWithLabel()
    {
       return [
           'program_id' => $this->program,
           'priority' => 1,
           'label' => 'Test Label'
       ];
    }

    private function mockLayoutRowWithNullLabel()
    {
       return [
           'program_id' => $this->program,
           'priority' => 1,
           'label' => ''
       ];
    }
}
