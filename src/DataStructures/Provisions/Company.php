<?php
/**
 * This file is part of the Payroll Calculator Package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author         Steeve Andrian Salim
 * @copyright      Copyright (c) Steeve Andrian Salim
 */
// ------------------------------------------------------------------------

namespace IrwanRuntuwene\IndonesiaPayrollCalculator\DataStructures\Provisions;

// ------------------------------------------------------------------------

/**
 * Class Company
 * @package IrwanRuntuwene\IndonesiaPayrollCalculator\DataStructures\Provisions
 */
class Company
{
    /**
     * Company::$numOfWorkingDays
     *
     * @var int
     */
    public $numOfWorkingDays = 25;

    /**
     * Company::$numOfWorkingHours
     *
     * @var int
     */
    public $numOfWorkingHours = 8;

    /**
     * Company::$calculateOvertime
     *
     * @var bool
     */
    public $calculateOvertime = true;

    /**
     * Company::$overtimeRate
     * 
     * @var int 
     */
    public $overtimeRate = 0;

    /**
     * Company::$calculateSplitShifts
     *
     * @var bool
     */
    public $calculateSplitShifts = true;

    /**
     * Company::$splitShiftsRate
     * 
     * @var int 
     */
    public $splitShiftsRate = 0;

    /**
     * Company::$calculateBPJSKesehatan
     *
     * @var bool
     */
    public $calculateBPJSKesehatan = true;

    /**
     * Company::$calculateBPJSKesehatan
     *
     * @var bool
     */
    public $calculateBPJSKetenagakerjaan = true;

    /**
     * Company::$JKK
     * 
     * @var bool 
     */
    public $JKK = false;

    /**
     * Company::$JKM
     *
     * @var bool
     */
    public $JKM = false;

    /**
     * Company::$JHT
     *
     * @var bool
     */
    public $JHT = false;

    /**
     * Company::$JIP
     *
     * @var bool
     */
    public $JIP = false;
    
    /**
     * Company::$riskGrade
     *
     * @var int
     */
    public $riskGrade = 2;

    /**
     * Company::$absentPenalty
     *
     * @var int
     */
    public $absentPenalty = 0;

    /**
     * Company::$latetimePenalty
     *
     * @var int
     */
    public $latetimePenalty = 0;

    /**
     * Company::$highestWageBPJSKesehatan
     *
     * @var int
     */
    public $highestWageBPJSKesehatan = 0;

    /**
     * Company::$highestWageBPJSKetenagakerjaan
     *
     * @var int
     */
    public $highestWageBPJSKetenagakerjaan = 0;
}
