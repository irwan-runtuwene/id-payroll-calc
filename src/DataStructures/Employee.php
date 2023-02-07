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

namespace IrwanRuntuwene\IndonesiaPayrollCalculator\DataStructures;

// ------------------------------------------------------------------------

/**
 * Class Employee
 * @package IrwanRuntuwene\IndonesiaPayrollCalculator\DataStructures
 */
class Employee
{
    /**
     * Employee::$permanentStatus
     *
     * @var bool
     */
    public $permanentStatus = true;


    
    public $prorate = false;



    public $actual = 22;



    public $gapok = 0;
    
    /**
     * Employee::$maritalStatus
     *
     * @var bool
     */
    public $maritalStatus = false;

    /**
     * Employee::$hasNPWP
     *
     * @var bool
     */
    public $hasNPWP = true;

    /**
     * Employee::$numOfDependentsFamily
     *
     * @var int
     */
    public $numOfDependentsFamily = 0;

    /**
     * Employee::$presences
     *
     * @var \IrwanRuntuwene\IndonesiaPayrollCalculator\DataStructures\Employee\Presences
     */
    public $presences;

    /**
     * Employee::$earnings
     *
     * @var \IrwanRuntuwene\IndonesiaPayrollCalculator\DataStructures\Employee\Earnings
     */
    public $earnings;

    /**
     * Employee::$allowances
     *
     * @var \IrwanRuntuwene\IndonesiaPayrollCalculator\DataStructures\Employee\Allowances
     */
    public $allowances;

    /**
     * Employee::$nonTaxAllowances
     *
     * @var \IrwanRuntuwene\IndonesiaPayrollCalculator\DataStructures\Employee\AnonTaxAllowances
     */
    public $nonTaxAllowances;

    /**
     * Employee::$deductions
     *
     * @var \IrwanRuntuwene\IndonesiaPayrollCalculator\DataStructures\Employee\Deductions
     */
    public $deductions;
    public $nonTaxDeductions;

    /**
     * Company::$calculateHolidayAllowance
     *
     * @var int
     */
    public $calculateHolidayAllowance = 0;

    /**
     * Employee::$bonus
     *
     * @var \IrwanRuntuwene\IndonesiaPayrollCalculator\DataStructures\Employee\Bonus
     */
    public $bonus;

    /**
     * Employee::$loans
     *
     * @var \IrwanRuntuwene\IndonesiaPayrollCalculator\DataStructures\Employee\Loans
     */
    public $loans;

    public $ptkpType;

    // ------------------------------------------------------------------------

    /**
     * Employee::__construct
     */
    public function __construct()
    {
        $this->presences = new Employee\Presences();
        $this->earnings = new Employee\Earnings();
        $this->allowances = new Employee\Allowances();
        $this->nonTaxAllowances = new Employee\NonTaxAllowances();
        $this->deductions = new Employee\Deductions();
        $this->bonus = new Employee\Bonus();
        $this->loans = new Employee\Loans();
        $this->nonTaxDeductions = new Employee\NonTaxDeductions();
    }
}
