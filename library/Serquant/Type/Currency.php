<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Type
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Type;

use Serquant\Type\Exception\InvalidArgumentException;

/**
 * Represents a currency.
 *
 * Currencies are identified by their ISO 4217 currency codes. See the ISO 4217
 * maintenance agency for more information, including
 * {@link http://www.currency-iso.org/iso_index/iso_tables/iso_tables_a1.htm
 * a table of currency codes}.
 *
 * The class is designed so that there's never more than one
 * <code>Currency</code> instance for any given currency. Therefore, there's
 * no public constructor. You obtain a <code>Currency</code> instance using
 * the <code>getInstance</code> methods.
 *
 * This class is base on java.util.Currency with simplifications regarding
 * localization.
 *
 * @category Serquant
 * @package  Type
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class Currency
{
    /**
     * ISO 4217 currency code for this currency.
     * @var string
     */
    private $currencyCode;

    /**
     * Default fraction digits for this currency.
     * Set from currency data tables.
     * @var integer
     */
    private $defaultFractionDigits;

    /**
     * ISO 4217 numeric code for this currency.
     * Set from currency data tables.
     * @var integer
     */
    private $numericCode;

    /**
     * Currency instances.
     * The class is designed so that there's never more than one Currency
     * instance for any given currency.
     * @var array
     */
    private static $instances = array();

    /**#@+
     * Index of the property in the ISO 4217 array
     * @var integer
     */
    private static $displayNameIndex = 0;
    private static $alphabeticCodeIndex = 1;
    private static $numericCodeIndex = 2;
    private static $minorUnitIndex = 3;
    private static $symbolIndex = 4;
    /**#@-*/

    /**
     * ISO 4217 list of currencies
     * @var array
     */
    private static $iso4217 = array(
        'AED' => array('UAE Dirham', 'AED', 784, 2, null),
        'AFN' => array('Afghani', 'AFN', 971, 2, null),
        'ALL' => array('Lek', 'ALL', 8, 2, null),
        'AMD' => array('Armenian Dram', 'AMD', 51, 2, null),
        'ANG' => array('Netherlands Antillean Guilder', 'ANG', 532, 2, null),
        'AOA' => array('Kwanza', 'AOA', 973, 2, null),
        'ARS' => array('Argentine Peso', 'ARS', 32, 2, null),
        'AUD' => array('Australian Dollar', 'AUD', 36, 2, null),
        'AWG' => array('Aruban Florin', 'AWG', 533, 2, null),
        'AZN' => array('Azerbaijanian Manat', 'AZN', 944, 2, null),
        'BAM' => array('Convertible Mark', 'BAM', 977, 2, null),
        'BBD' => array('Barbados Dollar', 'BBD', 52, 2, null),
        'BDT' => array('Taka', 'BDT', 50, 2, null),
        'BGN' => array('Bulgarian Lev', 'BGN', 975, 2, null),
        'BHD' => array('Bahraini Dinar', 'BHD', 48, 3, null),
        'BIF' => array('Burundi Franc', 'BIF', 108, 0, null),
        'BMD' => array('Bermudian Dollar', 'BMD', 60, 2, null),
        'BND' => array('Brunei Dollar', 'BND', 96, 2, null),
        'BOB' => array('Boliviano', 'BOB', 68, 2, null),
        'BOV' => array('Mvdol', 'BOV', 984, 2, null),
        'BRL' => array('Brazilian Real', 'BRL', 986, 2, null),
        'BSD' => array('Bahamian Dollar', 'BSD', 44, 2, null),
        'BTN' => array('Ngultrum', 'BTN', 64, 2, null),
        'BWP' => array('Pula', 'BWP', 72, 2, null),
        'BYR' => array('Belarussian Ruble', 'BYR', 974, 0, null),
        'BZD' => array('Belize Dollar', 'BZD', 84, 2, null),
        'CAD' => array('Canadian Dollar', 'CAD', 124, 2, null),
        'CDF' => array('Congolese Franc', 'CDF', 976, 2, null),
        'CHE' => array('WIR Euro', 'CHE', 947, 2, null),
        'CHF' => array('Swiss Franc', 'CHF', 756, 2, null),
        'CHW' => array('WIR Franc', 'CHW', 948, 2, null),
        'CLF' => array('Unidades de fomento', 'CLF', 990, 0, null),
        'CLP' => array('Chilean Peso', 'CLP', 152, 0, null),
        'CNY' => array('Yuan Renminbi', 'CNY', 156, 2, null),
        'COP' => array('Colombian Peso', 'COP', 170, 2, null),
        'COU' => array('Unidad de Valor Real', 'COU', 970, 2, null),
        'CRC' => array('Costa Rican Colon', 'CRC', 188, 2, null),
        'CUC' => array('Peso Convertible', 'CUC', 931, 2, null),
        'CUP' => array('Cuban Peso', 'CUP', 192, 2, null),
        'CVE' => array('Cape Verde Escudo', 'CVE', 132, 2, null),
        'CZK' => array('Czech Koruna', 'CZK', 203, 2, null),
        'DJF' => array('Djibouti Franc', 'DJF', 262, 0, null),
        'DKK' => array('Danish Krone', 'DKK', 208, 2, null),
        'DOP' => array('Dominican Peso', 'DOP', 214, 2, null),
        'DZD' => array('Algerian Dinar', 'DZD', 12, 2, null),
        'EGP' => array('Egyptian Pound', 'EGP', 818, 2, null),
        'ERN' => array('Nakfa', 'ERN', 232, 2, null),
        'ETB' => array('Ethiopian Birr', 'ETB', 230, 2, null),
        'EUR' => array('Euro', 'EUR', 978, 2, '€'),
        'FJD' => array('Fiji Dollar', 'FJD', 242, 2, null),
        'FKP' => array('Falkland Islands Pound', 'FKP', 238, 2, null),
        'GBP' => array('Pound Sterling', 'GBP', 826, 2, '£'),
        'GEL' => array('Lari', 'GEL', 981, 2, null),
        'GHS' => array('Cedi', 'GHS', 936, 2, null),
        'GIP' => array('Gibraltar Pound', 'GIP', 292, 2, null),
        'GMD' => array('Dalasi', 'GMD', 270, 2, null),
        'GNF' => array('Guinea Franc', 'GNF', 324, 0, null),
        'GTQ' => array('Quetzal', 'GTQ', 320, 2, null),
        'GYD' => array('Guyana Dollar', 'GYD', 328, 2, null),
        'HKD' => array('Hong Kong Dollar', 'HKD', 344, 2, null),
        'HNL' => array('Lempira', 'HNL', 340, 2, null),
        'HRK' => array('Croatian Kuna', 'HRK', 191, 2, null),
        'HTG' => array('Gourde', 'HTG', 332, 2, null),
        'HUF' => array('Forint', 'HUF', 348, 2, null),
        'IDR' => array('Rupiah', 'IDR', 360, 2, null),
        'ILS' => array('New Israeli Sheqel', 'ILS', 376, 2, null),
        'INR' => array('Indian Rupee', 'INR', 356, 2, null),
        'IQD' => array('Iraqi Dinar', 'IQD', 368, 3, null),
        'IRR' => array('Iranian Rial', 'IRR', 364, 2, null),
        'ISK' => array('Iceland Krona', 'ISK', 352, 0, null),
        'JMD' => array('Jamaican Dollar', 'JMD', 388, 2, null),
        'JOD' => array('Jordanian Dinar', 'JOD', 400, 3, null),
        'JPY' => array('Yen', 'JPY', 392, 0, null),
        'KES' => array('Kenyan Shilling', 'KES', 404, 2, null),
        'KGS' => array('Som', 'KGS', 417, 2, null),
        'KHR' => array('Riel', 'KHR', 116, 2, null),
        'KMF' => array('Comoro Franc', 'KMF', 174, 0, null),
        'KPW' => array('North Korean Won', 'KPW', 408, 2, null),
        'KRW' => array('Won', 'KRW', 410, 0, null),
        'KWD' => array('Kuwaiti Dinar', 'KWD', 414, 3, null),
        'KYD' => array('Cayman Islands Dollar', 'KYD', 136, 2, null),
        'KZT' => array('Tenge', 'KZT', 398, 2, null),
        'LAK' => array('Kip', 'LAK', 418, 2, null),
        'LBP' => array('Lebanese Pound', 'LBP', 422, 2, null),
        'LKR' => array('Sri Lanka Rupee', 'LKR', 144, 2, null),
        'LRD' => array('Liberian Dollar', 'LRD', 430, 2, null),
        'LSL' => array('Loti', 'LSL', 426, 2, null),
        'LTL' => array('Lithuanian Litas', 'LTL', 440, 2, null),
        'LVL' => array('Latvian Lats', 'LVL', 428, 2, null),
        'LYD' => array('Libyan Dinar', 'LYD', 434, 3, null),
        'MAD' => array('Moroccan Dirham', 'MAD', 504, 2, null),
        'MAD' => array('Moroccan Dirham', 'MAD', 504, 2, null),
        'MDL' => array('Moldovan Leu', 'MDL', 498, 2, null),
        'MGA' => array('Malagasy Ariary', 'MGA', 969, 2, null),
        'MKD' => array('Denar', 'MKD', 807, 2, null),
        'MMK' => array('Kyat', 'MMK', 104, 2, null),
        'MNT' => array('Tugrik', 'MNT', 496, 2, null),
        'MOP' => array('Pataca', 'MOP', 446, 2, null),
        'MRO' => array('Ouguiya', 'MRO', 478, 2, null),
        'MUR' => array('Mauritius Rupee', 'MUR', 480, 2, null),
        'MVR' => array('Rufiyaa', 'MVR', 462, 2, null),
        'MWK' => array('Kwacha', 'MWK', 454, 2, null),
        'MXN' => array('Mexican Peso', 'MXN', 484, 2, null),
        'MXV' => array('Mexican Unidad de Inversion (UDI)', 'MXV', 979, 2, null),
        'MYR' => array('Malaysian Ringgit', 'MYR', 458, 2, null),
        'MZN' => array('Metical', 'MZN', 943, 2, null),
        'NAD' => array('Namibia Dollar', 'NAD', 516, 2, null),
        'NGN' => array('Naira', 'NGN', 566, 2, null),
        'NIO' => array('Cordoba Oro', 'NIO', 558, 2, null),
        'NOK' => array('Norwegian Krone', 'NOK', 578, 2, null),
        'NPR' => array('Nepalese Rupee', 'NPR', 524, 2, null),
        'NZD' => array('New Zealand Dollar', 'NZD', 554, 2, null),
        'OMR' => array('Rial Omani', 'OMR', 512, 3, null),
        'PAB' => array('Balboa', 'PAB', 590, 2, null),
        'PEN' => array('Nuevo Sol', 'PEN', 604, 2, null),
        'PGK' => array('Kina', 'PGK', 598, 2, null),
        'PHP' => array('Philippine Peso', 'PHP', 608, 2, null),
        'PKR' => array('Pakistan Rupee', 'PKR', 586, 2, null),
        'PLN' => array('Zloty', 'PLN', 985, 2, null),
        'PYG' => array('Guarani', 'PYG', 600, 0, null),
        'QAR' => array('Qatari Rial', 'QAR', 634, 2, null),
        'RON' => array('Leu', 'RON', 946, 2, null),
        'RSD' => array('Serbian Dinar', 'RSD', 941, 2, null),
        'RUB' => array('Russian Ruble', 'RUB', 643, 2, null),
        'RWF' => array('Rwanda Franc', 'RWF', 646, 0, null),
        'SAR' => array('Saudi Riyal', 'SAR', 682, 2, null),
        'SBD' => array('Solomon Islands Dollar', 'SBD', 90, 2, null),
        'SCR' => array('Seychelles Rupee', 'SCR', 690, 2, null),
        'SDG' => array('Sudanese Pound', 'SDG', 938, 2, null),
        'SEK' => array('Swedish Krona', 'SEK', 752, 2, null),
        'SGD' => array('Singapore Dollar', 'SGD', 702, 2, null),
        'SHP' => array('Saint Helena Pound', 'SHP', 654, 2, null),
        'SLL' => array('Leone', 'SLL', 694, 2, null),
        'SOS' => array('Somali Shilling', 'SOS', 706, 2, null),
        'SRD' => array('Surinam Dollar', 'SRD', 968, 2, null),
        'SSP' => array('South Sudanese Pound', 'SSP', 728, 2, null),
        'STD' => array('Dobra', 'STD', 678, 2, null),
        'SVC' => array('El Salvador Colon', 'SVC', 222, 2, null),
        'SYP' => array('Syrian Pound', 'SYP', 760, 2, null),
        'SZL' => array('Lilangeni', 'SZL', 748, 2, null),
        'THB' => array('Baht', 'THB', 764, 2, null),
        'TJS' => array('Somoni', 'TJS', 972, 2, null),
        'TMT' => array('New Manat', 'TMT', 934, 2, null),
        'TND' => array('Tunisian Dinar', 'TND', 788, 3, null),
        'TOP' => array('Pa\'anga', 'TOP', 776, 2, null),
        'TRY' => array('Turkish Lira', 'TRY', 949, 2, null),
        'TTD' => array('Trinidad and Tobago Dollar', 'TTD', 780, 2, null),
        'TWD' => array('New Taiwan Dollar', 'TWD', 901, 2, null),
        'TZS' => array('Tanzanian Shilling', 'TZS', 834, 2, null),
        'UAH' => array('Hryvnia', 'UAH', 980, 2, null),
        'UGX' => array('Uganda Shilling', 'UGX', 800, 2, null),
        'USD' => array('US Dollar', 'USD', 840, 2, '$'),
        'USN' => array('US Dollar (Next day)', 'USN', 997, 2, null),
        'USS' => array('US Dollar (Same day)', 'USS', 998, 2, null),
        'UYI' => array('Uruguay Peso en Unidades Indexadas (URUIURUI)', 'UYI', 940, 0, null),
        'UYU' => array('Peso Uruguayo', 'UYU', 858, 2, null),
        'UZS' => array('Uzbekistan Sum', 'UZS', 860, 2, null),
        'VEF' => array('Bolivar Fuerte', 'VEF', 937, 2, null),
        'VND' => array('Dong', 'VND', 704, 0, null),
        'VUV' => array('Vatu', 'VUV', 548, 0, null),
        'WST' => array('Tala', 'WST', 882, 2, null),
        'XAF' => array('CFA Franc BEAC', 'XAF', 950, 0, null),
        'XAG' => array('Silver', 'XAG', 961, -1, null),
        'XAU' => array('Gold', 'XAU', 959, -1, null),
        'XBA' => array('Bond Markets Unit European Composite Unit (EURCO)', 'XBA', 955, -1, null),
        'XBB' => array('Bond Markets Unit European Monetary Unit (E.M.U.-6)', 'XBB', 956, -1, null),
        'XBC' => array('Bond Markets Unit European Unit of Account 9 (E.U.A.-9)', 'XBC', 957, -1, null),
        'XBD' => array('Bond Markets Unit European Unit of Account 17 (E.U.A.-17)', 'XBD', 958, -1, null),
        'XCD' => array('East Caribbean Dollar', 'XCD', 951, 2, null),
        'XDR' => array('SDR (Special Drawing Right)', 'XDR', 960, -1, null),
        'XFU' => array('UIC-Franc', 'XFU', 'Nil', -1, null),
        'XOF' => array('CFA Franc BCEAO', 'XOF', 952, 0, null),
        'XPD' => array('Palladium', 'XPD', 964, -1, null),
        'XPF' => array('CFP Franc', 'XPF', 953, 0, null),
        'XPT' => array('Platinum', 'XPT', 962, -1, null),
        'XSU' => array('Sucre', 'XSU', 994, -1, null),
        'XTS' => array('Codes specifically reserved for testing purposes', 'XTS', 963, -1, null),
        'XUA' => array('ADB Unit of Account', 'XUA', 965, -1, null),
        'XXX' => array('The codes assigned for transactions where no currency is involved', 'XXX', 999, -1, null),
        'YER' => array('Yemeni Rial', 'YER', 886, 2, null),
        'ZAR' => array('Rand', 'ZAR', 710, 2, null),
        'ZMK' => array('Zambian Kwacha', 'ZMK', 894, 2, null),
        'ZWL' => array('Zimbabwe Dollar', 'ZWL', 932, 2, null)
    );

    /**
     * Constructs a Currency instance.
     *
     * The constructor is private so that we can insure that there's never more
     * than one instance for a given currency.
     *
     * @param string $currencyCode ISO 4217 alphabetic code of the currency
     * @param integer $defaultFractionDigits Fraction digits
     * @param integer $numericCode ISO 4217 numeric code of the currency
     */
    private function __construct($currencyCode, $defaultFractionDigits, $numericCode)
    {
        $this->currencyCode = $currencyCode;
        $this->defaultFractionDigits = $defaultFractionDigits;
        $this->numericCode = $numericCode;
    }

    /**
     * Returns the Currency instance for the given currency code.
     *
     * @param string $currencyCode the ISO 4217 code of the currency
     * @return Currency instance for the given currency code
     * @throws InvalidArgumentException if $currencyCode is not a supported
     * ISO 4217 code.
     */
    public static function getInstance($currencyCode = null)
    {
        if (!isset(self::$instances[$currencyCode])) {
            if (!isset(self::$iso4217[$currencyCode])) {
                throw new InvalidArgumentException(
                    var_export($currencyCode, true) .
                    ' is not a supported ISO 4217 code.'
                );
            }
            $properties = self::$iso4217[$currencyCode];
            self::$instances[$currencyCode] = new self(
                $currencyCode,
                $properties[self::$minorUnitIndex],
                $properties[self::$numericCodeIndex]
            );
        }
        return self::$instances[$currencyCode];
    }

    /**
     * Gets the set of available currencies.
     *
     * @return array The set of available currencies.
     */
    public static function getAvailableCurrencies()
    {
        return array_keys(self::$iso4217);
    }

    /**
     * Gets the ISO 4217 currency code of this currency.
     *
     * @return string the ISO 4217 currency code of this currency.
     */
    public function getCurrencyCode()
    {
        return $this->currencyCode;
    }

    /**
     * Gets the symbol of this currency.
     *
     * For example, for the US Dollar, the symbol is "$".
     * If no symbol can be determined, the ISO 4217 currency code is returned.
     *
     * @return string the symbol of this currency
     */
    public function getSymbol()
    {
        $symbol = self::$iso4217[$this->currencyCode][self::$symbolIndex];
        return $symbol === null ? $this->currencyCode : $symbol;
    }

    /**
     * Gets the default number of fraction digits used with this currency.
     *
     * For example, the default number of fraction digits for the Euro is 2,
     * while for the Japanese Yen it's 0.
     * In the case of pseudo-currencies, such as Special Drawing Rights,
     * -1 is returned.
     *
     * @return int the default number of fraction digits used with this currency
     */
    public function getDefaultFractionDigits()
    {
        return $this->defaultFractionDigits;
    }

    /**
     * Returns the ISO 4217 numeric code of this currency.
     *
     * @return integer the ISO 4217 numeric code of this currency
     */
    public function getNumericCode()
    {
        return $this->numericCode;
    }

    /**
     * Gets the name that is suitable for displaying this currency.
     *
     * If there is no suitable display name found for the default locale, the
     * ISO 4217 currency code is returned.
     *
     * @return string the display name of this currency for the default locale
     */
    public function getDisplayName()
    {
        $name = self::$iso4217[$this->currencyCode][self::$displayNameIndex];
        return $name === null ? $this->currencyCode : $name;
    }

    /**
     * Returns the ISO 4217 currency code of this currency.
     *
     * @return string the ISO 4217 currency code of this currency
     */
    public function __toString()
    {
        return $this->currencyCode;
    }
}