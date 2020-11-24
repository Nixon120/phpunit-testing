<?php

namespace AllDigitalRewards\RewardStack\Services\Participant;

use ReflectionClass;

class CountryIsoCodeConversion
{
    const AF = '004';
    const AX = '248';
    const AL = '008';
    const DZ = '012';
    const AS = '016';
    const AD = '020';
    const AO = '024';
    const AI = '660';
    const AQ = '010';
    const AG = '028';
    const AR = '032';
    const AM = '051';
    const AW = '533';
    const AU = '036';
    const AT = '040';
    const AZ = '031';
    const BS = '044';
    const BH = '048';
    const BD = '050';
    const BB = '052';
    const BY = '112';
    const BE = '056';
    const BZ = '084';
    const BJ = '204';
    const BM = '060';
    const BT = '064';
    const BO = '068';
    const BA = '070';
    const BW = '072';
    const BV = '074';
    const BR = '076';
    const VG = '092';
    const IO = '086';
    const BN = '096';
    const BG = '100';
    const BF = '854';
    const BI = '108';
    const KH = '116';
    const CM = '120';
    const CA = '124';
    const CV = '132';
    const KY = '136';
    const CF = '140';
    const TD = '148';
    const CL = '152';
    const CN = '156';
    const HK = '344';
    const MO = '446';
    const CX = '162';
    const CC = '166';
    const CO = '170';
    const KM = '174';
    const CG = '178';
    const CD = '180';
    const CK = '184';
    const CR = '188';
    const CI = '384';
    const HR = '191';
    const CU = '192';
    const CY = '196';
    const CZ = '203';
    const DK = '208';
    const DJ = '262';
    const DM = '212';
    const DO = '214';
    const EC = '218';
    const EG = '818';
    const SV = '222';
    const GQ = '226';
    const ER = '232';
    const EE = '233';
    const ET = '231';
    const FK = '238';
    const FO = '234';
    const FJ = '242';
    const FI = '246';
    const FR = '250';
    const GF = '254';
    const PF = '258';
    const TF = '260';
    const GA = '266';
    const GM = '270';
    const GE = '268';
    const DE = '276';
    const GH = '288';
    const GI = '292';
    const GR = '300';
    const GD = '308';
    const GP = '312';
    const GU = '316';
    const GT = '320';
    const GG = '831';
    const GN = '324';
    const GW = '624';
    const GY = '328';
    const HT = '332';
    const HM = '334';
    const VA = '336';
    const HN = '340';
    const HU = '348';
    const IS = '352';
    const IN = '356';
    const ID = '360';
    const IR = '364';
    const IQ = '368';
    const IE = '372';
    const IM = '833';
    const IL = '376';
    const IT = '380';
    const JM = '388';
    const JP = '392';
    const JE = '832';
    const JO = '400';
    const KZ = '398';
    const KE = '404';
    const KI = '296';
    const KP = '408';
    const KR = '410';
    const KW = '414';
    const KG = '417';
    const LA = '418';
    const LV = '428';
    const LB = '422';
    const LS = '426';
    const LR = '430';
    const LY = '434';
    const LI = '438';
    const LT = '440';
    const LU = '442';
    const MK = '807';
    const MG = '450';
    const MW = '454';
    const MY = '458';
    const MV = '462';
    const ML = '466';
    const MT = '470';
    const MH = '584';
    const MQ = '474';
    const MR = '478';
    const MU = '480';
    const YT = '175';
    const MX = '484';
    const FM = '583';
    const MD = '498';
    const MC = '492';
    const MN = '496';
    const ME = '499';
    const MS = '500';
    const MA = '504';
    const MZ = '508';
    const MM = '104';
    const NA = '516';
    const NR = '520';
    const NP = '524';
    const NL = '528';
    const AN = '530';
    const NC = '540';
    const NZ = '554';
    const NI = '558';
    const NE = '562';
    const NG = '566';
    const NU = '570';
    const NF = '574';
    const MP = '580';
    const NO = '578';
    const OM = '512';
    const PK = '586';
    const PW = '585';
    const PS = '275';
    const PA = '591';
    const PG = '598';
    const PY = '600';
    const PE = '604';
    const PH = '608';
    const PN = '612';
    const PL = '616';
    const PT = '620';
    const PR = '630';
    const QA = '634';
    const RE = '638';
    const RO = '642';
    const RU = '643';
    const RW = '646';
    const BL = '652';
    const SH = '654';
    const KN = '659';
    const LC = '662';
    const MF = '663';
    const PM = '666';
    const VC = '670';
    const WS = '882';
    const SM = '674';
    const ST = '678';
    const SA = '682';
    const SN = '686';
    const RS = '688';
    const SC = '690';
    const SL = '694';
    const SG = '702';
    const SK = '703';
    const SB = '090';
    const SO = '706';
    const ZA = '710';
    const GS = '239';
    const SS = '728';
    const ES = '724';
    const LK = '144';
    const SD = '736';
    const SR = '740';
    const SJ = '744';
    const SZ = '748';
    const SE = '752';
    const CH = '756';
    const SY = '760';
    const TW = '158';
    const TJ = '762';
    const TZ = '834';
    const TH = '764';
    const TL = '626';
    const TG = '768';
    const TK = '772';
    const TO = '776';
    const TT = '780';
    const TN = '788';
    const TR = '792';
    const TM = '795';
    const TC = '796';
    const TV = '798';
    const UG = '800';
    const UA = '804';
    const AE = '784';
    const GB = '826';
    const US = '840';
    const UM = '581';
    const UY = '858';
    const UZ = '860';
    const VU = '548';
    const VE = '862';
    const VN = '704';
    const VI = '850';
    const WF = '876';
    const EH = '732';
    const YE = '887';
    const ZM = '894';
    const ZW = '716';

    /**
     * @param $country
     * @return string
     */
    public static function hydrateCountryCode($country)
    {
        if (empty($country) === false) {
            $values = self::getConstants();
            foreach ($values as $key => $value) {
                if ($country === (int)$value) {
                    return $key;
                }
            }
        }
        return '';
    }

    /**
     * @return array
     */
    private static function getConstants()
    {
        $oClass = new ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }
}