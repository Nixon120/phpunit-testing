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

        $this->assertSame('', $program->getCostCenterId());
    }

    /**
     * Regression test to ensure that a 0 (default) is returned when no deposit amount is set.
     */
    public function testDepositAmountReturnsZeroWhenNotSet()
    {
        $program = new Program();

        $this->assertSame(0, $program->getDepositAmount());
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

    public function testProgramIsActiveAndNotExpiredDueToEndDateReturnsFalse()
    {
        //dont set h:i:s so it defaults to beginning of day for testing purposes
        $endDate = date("Y-m-d", strtotime("now"));
        $program = new Program();
        $program->setActive(1);
        $program->setGracePeriod(0);
        $program->setEndDate($endDate);

        $this->assertFalse($program->isActiveAndNotExpired());
    }

    public function testProgramIsActiveAndNotExpiredDueToActiveStatusReturnsFalse()
    {
        $endDate = date("Y-m-d", strtotime("tomorrow"));
        $program = new Program();
        $program->setActive(0);
        $program->setGracePeriod(0);
        $program->setEndDate($endDate);

        $this->assertFalse($program->isActiveAndNotExpired());
    }

    public function testProgramIsActiveAndNotExpiredReturnsTrue()
    {
        //dont set h:i:s so it defaults to beginning of day for testing purposes
        $endDate = date("Y-m-d", strtotime("tomorrow"));
        $program = new Program();
        $program->setActive(1);
        $program->setGracePeriod(0);
        $program->setEndDate($endDate);

        $this->assertTrue($program->isActiveAndNotExpired());
    }

    public function testProgramIsActiveAndNotExpiredDueToGracePeriodDaysReturnsTrue()
    {
        //dont set h:i:s so it defaults to beginning of day for testing purposes
        $endDate = date("Y-m-d", strtotime("yesterday"));
        $program = new Program();
        $program->setActive(1);
        $program->setGracePeriod(3);
        $program->setEndDate($endDate);

        $this->assertTrue($program->isActiveAndNotExpired());
    }

    public function testProductCriteriaJsonFormattingFilterReturnsSameStructure()
    {
        $jsonCriteria = $this->jsonProductCriteriaWithMissingAttributes();
        $criteria = new \Entities\ProductCriteria();
        $criteria->setFilter($jsonCriteria);
        $expectedDecoded = json_decode($this->jsonProductCriteriaExpectedResponse());
        $decodedFilter = json_decode($criteria->getFilter());

        $this->assertTrue($expectedDecoded == $decodedFilter);
    }

    public function testProductCriteriaArrayFormattingFilterReturnsSameStructure()
    {
        $jsonCriteria = $this->arrayProductCriteriaWithMissingAttributes();
        $criteria = new \Entities\ProductCriteria();
        $criteria->setFilter($jsonCriteria);
        $expectedDecoded = json_decode($this->jsonProductCriteriaExpectedResponse());
        $decodedFilter = json_decode($criteria->getFilter());

        $this->assertTrue($expectedDecoded == $decodedFilter);
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

    private function jsonProductCriteriaExpectedResponse()
    {
        return '
            {
                "price":{"min":"","max":""},
                "products":["IP0011199160-493","VVISA01","GAME2","WORVISA01","GAME5","ASIP0011334630-4925VV","GAME7","GAME6","GAME1","IP0011199160-492","IP0011334630-4950VV","IP0011334630-49VV","IP0011199160-494","IP0011199160-491","GAME8","PVISA01","IP0011199160-49","GAME16","WBIC120","GAME4"],
                "exclude_products":[],
                "exclude_brands":[],
                "exclude_vendors":["omnicard"],
                "category":["10","13","16","21","23","1","11","26","1000001","44","6","53","71","83","86","89","12","92","95"],
                "brand":[],
                "group":[]
            }
        ';
    }

    private function jsonProductCriteriaWithMissingAttributes()
    {
        return '
            {
                "min": "",
                "max": "",
                "products":["IP0011199160-493","VVISA01","GAME2","WORVISA01","GAME5","ASIP0011334630-4925VV","GAME7","GAME6","GAME1","IP0011199160-492","IP0011334630-4950VV","IP0011334630-49VV","IP0011199160-494","IP0011199160-491","GAME8","PVISA01","IP0011199160-49","GAME16","WBIC120","GAME4"],
                "exclude_products":[],
                "exclude_brands":[],
                "exclude_vendors":["omnicard"],
                "categories":["10","13","16","21","23","1","11","26","1000001","44","6","53","71","83","86","89","12","92","95"],
                "brands":[]
            }
        ';
    }

    private function arrayProductCriteriaWithMissingAttributes()
    {
        return [

                "min"=> "",
                "max"=> "",
                "products"=> ["IP0011199160-493","VVISA01","GAME2","WORVISA01","GAME5","ASIP0011334630-4925VV","GAME7","GAME6","GAME1","IP0011199160-492","IP0011334630-4950VV","IP0011334630-49VV","IP0011199160-494","IP0011199160-491","GAME8","PVISA01","IP0011199160-49","GAME16","WBIC120","GAME4"],
                "exclude_products"=>[],
                "exclude_brands"=>[],
                "exclude_vendors"=> ["omnicard"],
                "categories"=>["10","13","16","21","23","1","11","26","1000001","44","6","53","71","83","86","89","12","92","95"],
                "brands"=>[]
        ];
    }
}
