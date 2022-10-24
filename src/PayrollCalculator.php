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

namespace IrwanRuntuwene\IndonesiaPayrollCalculator;

// ------------------------------------------------------------------------

use O2System\Spl\DataStructures\SplArrayObject;
use IrwanRuntuwene\IndonesiaPayrollCalculator\DataStructures;
use IrwanRuntuwene\IndonesiaPayrollCalculator\Taxes\Pph21;
use IrwanRuntuwene\IndonesiaPayrollCalculator\Taxes\Pph23;
use IrwanRuntuwene\IndonesiaPayrollCalculator\Taxes\Pph26;

/**
 * Class PayrollCalculator
 * @package Steevenz\IndonesiaPayrollCalculator
 */
class PayrollCalculator
{
    /**
     * PayrollCalculator::NETT_CALCULATION
     *
     * PPh 21 ditanggung oleh perusahaan atau penyedia kerja.
     *
     * @var string
     */
    const NETT_CALCULATION = 'NETT';

    /**
     * PayrollCalculator::GROSS_CALCULATION
     *
     * PPh 21 ditanggung oleh pekerja/karyawan.
     *
     * @var string
     */
    const GROSS_CALCULATION = 'GROSS';

    /**
     * PayrollCalculator::GROSS_UP_CALCULATION
     *
     * Tanggungan PPh 21 ditambahkan sebagai tunjangan pekerja/karyawan.
     *
     * @var string
     */
    const GROSS_UP_CALCULATION = 'GROSSUP';

    /**
     * PayrollCalculator::$provisions
     *
     * @var \Steevenz\IndonesiaPayrollCalculator\DataStructures\Provisions
     */
    public $provisions;

    /**
     * PayrollCalculator::$employee
     *
     * @var \Steevenz\IndonesiaPayrollCalculator\DataStructures\Employee
     */
    public $employee;

    /**
     * PayrollCalculator::$taxNumber
     *
     * @var int
     */
    public $taxNumber = 21;

    /**
     * PayrollCalculator::$method
     *
     * @var string
     */
    public $method = 'NETTO';

    /**
     * PayrollCalculator::$result
     *
     * @var SplArrayObject
     */
    public $result;

    // ------------------------------------------------------------------------

    /**
     * PayrollCalculator::__construct
     *
     * @param array $data
     */
    public function __construct()
    {
        $this->provisions = new DataStructures\Provisions();
        $this->employee = new DataStructures\Employee();
        $this->result = new SplArrayObject([
            'earnings'    => new SplArrayObject([
                'base'           => 0,
                'gaji_plus_tunjangan' => 0,
                'fixedAllowance' => 0,
                'nonTaxAllowance' => 0,
                'annualy'        => new SplArrayObject([
                    'nett'  => 0,
                    'gross' => 0,
                ]),
            ]),
            'takeHomePay' => 0,
            'loans' => 0,
        ]);
    }

    // ------------------------------------------------------------------------

    /**
     * PayrollCalculator::getCalculation
     *
     * @return \O2System\Spl\DataStructures\SplArrayObject
     */
    public function getCalculation()
    {
        if ($this->taxNumber == 21) {
            return $this->calculateBaseOnPph21();
        } 
        // elseif ($this->taxNumber == 23) {
        //     return $this->calculateBaseOnPph23();
        // } elseif ($this->taxNumber == 26) {
        //     return $this->calculateBaseOnPph26();
        // }
    }

    // ------------------------------------------------------------------------

    /**
     * PayrollCalculator::calculateBaseOnPph21
     *
     * @return \O2System\Spl\DataStructures\SplArrayObject
     */
    private function calculateBaseOnPph21()
    {
        // Gaji + Penghasilan teratur
        $this->result->earnings->base = $this->employee->earnings->base;
        $this->result->earnings->fixedAllowance = $this->employee->earnings->fixedAllowance;

        // Penghasilan bruto bulanan merupakan gaji pokok ditambah tunjangan tetap
        $this->result->earnings->gross = $this->result->earnings->base + $this->employee->earnings->fixedAllowance;

        if ($this->employee->calculateHolidayAllowance > 0) {
            $this->result->earnings->holidayAllowance = $this->employee->calculateHolidayAllowance * $this->result->earnings->gross;
        }

        // Penghasilan tidak teratur
        if ($this->provisions->company->calculateOvertime === true) {
            if($this->provisions->state->overtimeRegulationCalculation) {
                //  Berdasarkan Kepmenakertrans No. 102/MEN/VI/2004
                if ($this->employee->presences->overtime > 1) {
                    $overtime1stHours = 1 * 1.5 * 1 / 173 * $this->result->earnings->gross;
                    $overtime2ndHours = ($this->employee->presences->overtime - 1) * 2 * 1 / 173 * $this->result->earnings->gross;
                    $this->result->earnings->overtime = $overtime1stHours + $overtime2ndHours;
                } else {
                    $this->result->earnings->overtime = $this->employee->presences->overtime * 1.5 * 1 / 173 * $this->result->earnings->gross;
                }
            } else {
                if($this->provisions->company->overtimeRate > 0) {
                    $this->provisions->company->overtimeRate = ($this->employee->presences->overtime / $this->provisions->company->numOfWorkingDays / $this->provisions->company->numOfWorkingHours);
                }

                $this->result->earnings->overtime = $this->employee->presences->overtime * $this->provisions->company->overtimeRate;
            }

            $this->result->earnings->overtime = ($this->result->earnings->overtime);

            // Lembur ditambahkan sebagai pendapatan bruto bulanan
            $this->result->earnings->gross = $this->result->earnings->gross + $this->result->earnings->overtime;
        }
        
        if($this->provisions->company->calculateSplitShifts) {
            $this->result->earnings->splitShifts = $this->provisions->company->splitShiftsRate * $this->employee->presences->splitShifts;

            // Split Shift ditambahkan sebagai pendapatan bruto bulanan
            $this->result->earnings->gross = $this->result->earnings->gross + $this->result->earnings->splitShifts;
        }

        $this->result->earnings->annualy->gross = $this->result->earnings->gross * 12;

        if ($this->employee->permanentStatus === false) {
            $this->company->allowances->BPJSKesehatan = 0;
            $this->employee->nonTaxDeductions->BPJSKesehatan = 0;

            $this->employee->allowances->JKK = 0;
            $this->employee->allowances->JKM = 0;

            $this->employee->allowances->JHT = 0;
            $this->employee->deductions->JHT = 0;

            $this->employee->allowances->JIP = 0;
            $this->employee->deductions->JIP = 0;

            // Set result allowances, bonus, deductions
            $this->result->offsetSet('allowances', $this->employee->allowances);
            $this->result->offsetSet('bonus', $this->employee->bonus);
            $this->result->offsetSet('deductions', $this->employee->deductions);
            $this->result->offsetSet('nonTaxAllowances', $this->employee->nonTaxAllowances);
            $this->result->offsetSet('nonTaxDeductions', $this->employee->nonTaxDeductions);
            $this->result->offsetSet('loans', $this->employee->loans);

            // print_r($this->result->allowances); die;
            // Pendapatan bersih
            $this->result->earnings->nett = $this->result->earnings->gross + $this->result->allowances->getSum() - $this->result->deductions->getSum();
            $this->result->earnings->annualy->nett = $this->result->earnings->nett * 12;


            $this->result->offsetSet('taxable', (new Pph21($this))->result);

            // Pengurangan Penalty
            $this->employee->deductions->offsetSet('penalty', new SplArrayObject([
                'late'   => $this->employee->presences->latetime * $this->provisions->company->latetimePenalty,
                'absent' => $this->employee->presences->absentDays * $this->provisions->company->absentPenalty,
            ]));

            // Tunjangan Hari Raya
            if ($this->employee->earnings->holidayAllowance > 0) {
                $this->result->allowances->offsetSet('holiday', $this->employee->earnings->holidayAllowance);
            }
            
            $this->result->takeHomePay = $this->result->earnings->nett + $this->employee->earnings->holidayAllowance + $this->employee->bonus->getSum() - $this->employee->deductions->penalty->getSum() + $this->employee->nonTaxAllowances->getSum();
            $this->result->allowances->offsetSet('positionTax', 0);
            $this->result->allowances->offsetSet('pph21Tax', 0);
        
        } else {
            // jika permanent employee

            // print_r($this->provisions->company);
            // die;

            if ($this->provisions->company->calculateBPJSKesehatan === true) {
                // Calculate BPJS Kesehatan Allowance & Deduction
                if ($this->result->earnings->gross < $this->provisions->company->highestWageBPJSKesehatan) {
                    $this->company->allowances->BPJSKesehatan = ( $this->result->earnings->gross * (4 / 100)) ;
                    $this->employee->allowances->BPJSKesehatan = ( $this->result->earnings->gross * (4 / 100)) ;
                } else {
                    $this->company->allowances->BPJSKesehatan = 
                        $this->provisions->company->highestWageBPJSKesehatan * (4 / 100);
                    $this->employee->allowances->BPJSKesehatan = 
                        $this->provisions->company->highestWageBPJSKesehatan * (4 / 100);
                }

                // Maximum number of dependents family is 5
                if ($this->employee->numOfDependentsFamily > 5) {
                    $this->employee->nonTaxDeductions->BPJSKesehatan = $this->employee->nonTaxDeductions->BPJSKesehatan + ($this->employee->nonTaxDeductions->BPJSKesehatan * ($this->employee->numOfDependentsFamily - 5));
                }
            }

            // jika bpjs tk dihitung maka masuk logic ini
            if ($this->provisions->company->calculateBPJSKetenagakerjaan === true) {

            if ($this->provisions->company->JKK === true) {
                if ($this->result->earnings->gross < $this->provisions->state->highestWage) {

                    $this->company->allowances->JKK = ( $this->result->earnings->gross * ($this->provisions->state->getJKKRiskGradePercentage($this->provisions->company->riskGrade) / 100) );

                    $this->employee->allowances->JKK = ( $this->result->earnings->gross * ($this->provisions->state->getJKKRiskGradePercentage($this->provisions->company->riskGrade) / 100) );

                } elseif ($this->result->earnings->gross >= $this->provisions->state->provinceMinimumWage && $this->result->earnings->gross >= $this->provisions->state->highestWage) {

                    $this->company->allowances->JKK = ( $this->provisions->state->highestWage * ($this->provisions->state->getJKKRiskGradePercentage($this->provisions->company->riskGrade) / 100) );

                    $this->employee->allowances->JKK = ( $this->provisions->state->highestWage * ($this->provisions->state->getJKKRiskGradePercentage($this->provisions->company->riskGrade) / 100) );
                }
            }

            if ($this->provisions->company->JKM === true) {
                if ($this->result->earnings->gross < $this->provisions->state->highestWage) {
                    $this->company->allowances->JKM = ( $this->result->earnings->gross * (0.30 / 100) );
                    $this->employee->allowances->JKM = ( $this->result->earnings->gross * (0.30 / 100) );
                } elseif ($this->result->earnings->gross >= $this->provisions->state->provinceMinimumWage && $this->result->earnings->gross >= $this->provisions->state->highestWage) {
                    $this->company->allowances->JKM = ( $this->provisions->state->highestWage * (0.30 / 100) );
                    $this->employee->allowances->JKM = ( $this->provisions->state->highestWage * (0.30 / 100) );
                }
            }

            if ($this->provisions->company->JHT === true) {
                if ($this->result->earnings->gross < $this->provisions->state->highestWage) {
                    $this->company->allowances->JHT = ( $this->result->earnings->gross * (3.7 / 100) );
                    $this->employee->allowances->JHT = ( $this->result->earnings->gross * (3.7 / 100) );
                    $this->employee->deductions->JHT = ( $this->result->earnings->gross * (2 / 100));
                } elseif ($this->result->earnings->gross >= $this->provisions->state->provinceMinimumWage && $this->result->earnings->gross >= $this->provisions->state->highestWage) {
                    $this->company->allowances->JHT = ( $this->provisions->state->highestWage * (3.7 / 100));
                    $this->employee->allowances->JHT = ( $this->provisions->state->highestWage * (3.7 / 100));
                    $this->employee->deductions->JHT = ( $this->provisions->state->highestWage * (2 / 100));
                }
            }

            if ($this->provisions->company->JIP === true) {
                if ($this->result->earnings->gross < $this->provisions->state->highestWage) {
                    $this->employee->allowances->JIP = ( $this->result->earnings->gross * (2 / 100) );
                    $this->company->allowances->JIP = ( $this->result->earnings->gross * (2 / 100) );
                    $this->employee->deductions->JIP = ( $this->result->earnings->gross * (1 / 100) );

                } elseif ($this->result->earnings->gross >= $this->provisions->state->provinceMinimumWage && $this->result->earnings->gross >= $this->provisions->state->highestWage) {
                    $this->company->allowances->JIP = ( $this->provisions->state->highestWage * (2 / 100) );
                    $this->employee->allowances->JIP = ( $this->provisions->state->highestWage * (2 / 100) );
                    $this->employee->deductions->JIP = ( $this->provisions->state->highestWage * (1 / 100) );
                }
            }

            } else {
                $this->company->allowances->JKK = 0;
                $this->employee->allowances->JKK = 0;
                $this->company->allowances->JKM = 0;
                $this->employee->allowances->JKM = 0;
                $this->employee->allowances->JHT = 0;
                $this->company->allowances->JHT = 0;
                $this->employee->deductions->JHT = 0;
                $this->employee->allowances->JIP = 0;
                $this->company->allowances->JIP = 0;
                $this->employee->deductions->JIP = 0;
            }


            // Set result allowances, bonus, deductions
            // print_r($this->employee->allowances); die; 

            $this->result->offsetSet('allowances', $this->employee->allowances);
            $this->result->offsetSet('bonus', $this->employee->bonus);
            $this->result->offsetSet('deductions', $this->employee->deductions);
            $this->result->offsetSet('nonTaxAllowances', $this->employee->nonTaxAllowances);
            $this->result->offsetSet('loans', $this->employee->loans);

            // hitung gross plus tunjangan bpjs
            // print_r($this->result->earnings->base); die;
            // print_r($this->result->allowances->getSum()); die; 

            // print_r($grossPlusBPJS); die;

            // $monthlyPositionTax = 0;
            // if ($grossPlusBPJS > $this->provisions->state->provinceMinimumWage) {

            //     /**
            //      * According to Undang-Undang Direktur Jenderal Pajak Nomor PER-32/PJ/2015 Pasal 21 ayat 3
            //      * Position Deduction is 5% from Annual Gross Income
            //      */
            //     $monthlyPositionTax = $grossPlusBPJS * (5 / 100);

            //     /**
            //      * Maximum Position Deduction in Indonesia is 500000 / month
            //      * or 6000000 / year
            //      */
            //     if ($monthlyPositionTax >= 500000) {
            //         $monthlyPositionTax = 500000;
            //     }
            // }
            
            // set deduction presence if not presence
            $unWork = $this->provisions->company->numOfWorkingDays - $this->employee->presences->workDays;

            if ($unWork > 0) {
                $this->result->deductions->offsetSet('presence',
                    $this->employee->earnings->base / $this->provisions->company->numOfWorkingDays * $unWork
                );
            }

            // Pendapatan bersih
            
            // sebelum dikurang biaya jabatan
            // print_r($this->result->allowances); die;
            

            //   - $monthlyPositionTax

            if( $this->provisions->company->calculateBPJSKesehatan ){

                if ($this->result->earnings->base < $this->provisions->company->highestWageBPJSKesehatan) {
                    $this->employee->nonTaxDeductions->BPJSKesehatan = $this->result->earnings->base * (1 / 100);
                } else {
                    $this->employee->nonTaxDeductions->BPJSKesehatan = $this->provisions->company->highestWageBPJSKesehatan * (1 / 100);
                }

            } else {

                    $this->employee->nonTaxDeductions->BPJSKesehatan = 0;

            }


            // print_r($this->result->deductions);

            
            // print_r($this->result->allowances); die;

            // total tunjangan
            $totalTunjangan = $this->employee->allowances['tunjanganMakan'] + $this->employee->allowances['transport'] + $this->employee->allowances['pulsa'] + $this->employee->allowances['lain-lain'] + $this->employee->nonTaxAllowances->fasilitas;
            
            $totalGaji = $this->result->earnings->base + $totalTunjangan - $this->employee->nonTaxAllowances->fasilitas;
            


            // print_r([
            //     $totalTunjangan,
            //     $totalGaji
            // ]
            // ); die;

            $nett = $this->result->earnings->base + $this->result->allowances->getSum(); // AE
            $monthlyPositionTax = ($nett) * (5/100) >= 500000 ? 500000 : $nett * (5/100);
            $this->result->earnings->monthlyPositionTax = $monthlyPositionTax;

            $this->result->earnings->nett = $nett - $monthlyPositionTax - $this->result->deductions->getSum(); // AK21
            $this->result->earnings->nettFacility = $this->result->earnings->nett + $this->employee->nonTaxAllowances->fasilitas; // AK21
            $this->result->earnings->total_base =  $this->result->earnings->base + ( $this->result->allowances->getSum() - $this->result->allowances->BPJSKesehatan -  $this->result->allowances->JKM -  $this->result->allowances->JIP -  $this->result->allowances->JHT -  $this->result->allowances->JKK ) ;
            // print_r($this->result->earnings->total_base); die;
            $this->result->earnings->gross = $this->result->earnings->total_base + $this->company->allowances->BPJSKesehatan +  $this->company->allowances->JKK + $this->company->allowances->JKM + $this->company->allowances->JHT + $this->company->allowances->JIP;
            $this->result->earnings->grossFacility = $this->result->earnings->total_base + $this->company->allowances->BPJSKesehatan +  $this->company->allowances->JKK + $this->company->allowances->JKM + $this->company->allowances->JHT + $this->company->allowances->JIP + $this->employee->nonTaxAllowances->fasilitas; //
            // biaya jabatan , jHT, jip = pengurang

            // print_r([
            //     $monthlyPositionTax,
            //     // $this->result->earnings->base,
            //     // $this->result->earnings->gross,
            //     $this->result->earnings->base + $this->result->allowances->getSum(),
            //     // $this->result->deductions,
            //     $nett,
            //     $this->result->earnings->nett,
            //     $this->result->earnings->monthlyPositionTax
            // ]); die;
            // echo $this->result->earnings->base + $this->result->allowances->getSum();
            // $this->result->earnings->nett_tax = $this->result->earnings->nett - ( $this->result->deductions->JHT + $this->result->deductions->JIP);

            
            
            // print_r($monthlyPositionTax + $this->result->deductions->JHT + $this->result->deductions->JIP);


            // print_r($this->result->earnings->nett); die;

            // $grossPlusBPJS = $this->result->earnings->base + $this->result->allowances->getSum();
            // $monthlyPositionTax = ($this->result->earnings->nett_tax) * (5/100) >= 500000 ? 500000 : $this->result->earnings->nett_tax * (5/100);

            
            $this->result->earnings->gaji_plus_tunjangan = $this->employee->allowances->getSum();
            // $this->result->earnings->annualy->nett = round( ( $this->result->earnings->nett_tax )  * 12 );
            $this->result->earnings->annualy->nett = round( ( $this->result->earnings->nett)  * 12 );
            $this->result->earnings->annualy->nettFacility = round( ( $this->result->earnings->nettFacility)  * 12 );
            

            $this->result->offsetSet('taxable', (new Pph21($this))->calculate());
            $this->result->offsetSet('company', $this->company->allowances);


            // Pengurangan Penalty
            $this->employee->deductions->offsetSet('penalty', new SplArrayObject([
                'late'   => $this->employee->presences->latetime * $this->provisions->company->latetimePenalty,
                'absent' => $this->employee->presences->absentDays * $this->provisions->company->absentPenalty,
            ]));

            // Tunjangan Hari Raya
            if ($this->employee->earnings->holidayAllowance > 0) {
                $this->result->allowances->offsetSet('holiday', $this->employee->earnings->holidayAllowance);
            }

            

            switch ($this->method) {
                // Pajak ditanggung oleh perusahaan
                case self::NETT_CALCULATION:
                    $this->result->takeHomePay = $this->result->earnings->nett + $this->employee->earnings->holidayAllowance + $this->employee->bonus->getSum() - $this->employee->deductions->penalty->getSum() + $this->result->nonTaxAllowances->getSum();
                    $this->result->company->allowances->offsetSet('positionTax', $monthlyPositionTax);
                    $this->result->company->allowances->offsetSet('pph21Tax',
                        $this->result->taxable->liability->monthly);
                    break;
                // Pajak ditanggung oleh karyawan
                case self::GROSS_CALCULATION:
                    // bas + makan , transport, pulsa bpjs kes jkk jkn jht jip - jht jip bjps kes
                    // print_r($this->result->earnings->base + $this->employee->allowances['tunjanganMakan'] + $this->employee->allowances['transport'] + $this->employee->allowances['pulsa']);
                    $penambah = ($this->result->earnings->base + $this->employee->allowances['tunjanganMakan'] + $this->employee->allowances['transport'] + $this->employee->allowances['pulsa'] + $this->employee->allowances['lain-lain'] + $this->employee->nonTaxAllowances->fasilitas);
                    
                    $pengurang = ($this->employee->nonTaxDeductions->BPJSKesehatan + $this->employee->deductions->JIP + $this->employee->deductions->JHT + $this->result->taxable->liability->monthly + $this->employee->loans->getSum());
                    
                    // print_r($pengurang); die; 

                    $this->result->takeHomePay = $penambah - $pengurang;

                    $this->result->deductions->offsetSet('positionTax', $monthlyPositionTax);
                    $this->result->deductions->offsetSet('pph21Tax', $this->result->taxable->liability->monthly);
                    $this->result->deductions->offsetSet('BPJSKesehatan', $this->employee->nonTaxDeductions->BPJSKesehatan);
                    break;
                // Pajak ditanggung oleh perusahaan sebagai tunjangan pajak.
                case self::GROSS_UP_CALCULATION:
                    $this->result->takeHomePay = $this->result->earnings->nett + $this->employee->earnings->holidayAllowance + $this->employee->bonus->getSum() - $this->employee->deductions->penalty->getSum();
                    $this->result->deductions->offsetSet('positionTax', $monthlyPositionTax);
                    $this->result->deductions->offsetSet('pph21Tax', $this->result->taxable->liability->monthly);
                    break;
            }
        }

        return $this->result;
    }

    // ------------------------------------------------------------------------

    /**
     * PayrollCalculator::calculateBaseOnPph23
     *
     * @return \O2System\Spl\DataStructures\SplArrayObject
     */
    private function calculateBaseOnPph23()
    {
        // Gaji + Penghasilan teratur
        $this->result->earnings->base = $this->employee->earnings->base;
        $this->result->earnings->fixedAllowance = $this->employee->earnings->fixedAllowance;

        // Penghasilan bruto bulanan merupakan gaji pokok ditambah tunjangan tetap
        $this->result->earnings->gross = $this->result->earnings->base + $this->employee->earnings->fixedAllowance;

        if ($this->employee->calculateHolidayAllowance > 0) {
            $this->result->earnings->holidayAllowance = $this->employee->calculateHolidayAllowance * $this->result->earnings->gross;
        }

        // Set result allowances, bonus, deductions
        $this->result->offsetSet('allowances', $this->employee->allowances);
        $this->result->offsetSet('bonus', $this->employee->bonus);
        $this->result->offsetSet('deductions', $this->employee->deductions);

        // Pendapatan bersih
        $this->result->earnings->nett = $this->result->earnings->gross + $this->result->allowances->getSum() - $this->result->deductions->getSum();
        $this->result->earnings->annualy->nett = ( $this->result->earnings->nett ) * 12;

        $this->result->offsetSet('taxable', (new Pph23($this))->calculate());

        switch ($this->method) {
            // Pajak ditanggung oleh perusahaan
            case self::NETT_CALCULATION:
                $this->result->takeHomePay = $this->result->earnings->nett + $this->employee->earnings->holidayAllowance + $this->employee->bonus->getSum();
                break;
            // Pajak ditanggung oleh karyawan
            case self::GROSS_CALCULATION:
                $this->result->takeHomePay = $this->result->earnings->nett + $this->employee->bonus->getSum() - $this->result->taxable->liability->amount;
                $this->result->deductions->offsetSet('pph23Tax', $this->result->taxable->liability->amount);
                break;
            // Pajak ditanggung oleh perusahaan sebagai tunjangan pajak.
            case self::GROSS_UP_CALCULATION:
                $this->result->takeHomePay = $this->result->earnings->nett + $this->employee->bonus->getSum();
                $this->result->allowances->offsetSet('pph23Tax', $this->result->taxable->liability->amount);
                break;
        }

        return $this->result;
    }

    // ------------------------------------------------------------------------

    /**
     * PayrollCalculator::calculateBaseOnPph26
     *
     * @return \O2System\Spl\DataStructures\SplArrayObject
     */
    private function calculateBaseOnPph26()
    {
        // Gaji + Penghasilan teratur
        $this->result->earnings->base = $this->employee->earnings->base;
        $this->result->earnings->fixedAllowance = $this->employee->earnings->fixedAllowance;

        // Penghasilan bruto bulanan merupakan gaji pokok ditambah tunjangan tetap
        $this->result->earnings->gross = $this->result->earnings->base + $this->employee->earnings->fixedAllowance;

        if ($this->employee->calculateHolidayAllowance > 0) {
            $this->result->earnings->holidayAllowance = $this->employee->calculateHolidayAllowance * $this->result->earnings->gross;
        }

        // Set result allowances, bonus, deductions
        $this->result->offsetSet('allowances', $this->employee->allowances);
        $this->result->offsetSet('bonus', $this->employee->bonus);
        $this->result->offsetSet('deductions', $this->employee->deductions);

        // Pendapatan bersih
        $this->result->earnings->nett = $this->result->earnings->gross + $this->result->allowances->getSum() - $this->result->deductions->getSum();
        $this->result->earnings->annualy->nett = $this->result->earnings->nett * 12;

        $this->result->offsetSet('taxable', (new Pph26($this))->calculate());

        switch ($this->method) {
            // Pajak ditanggung oleh perusahaan
            case self::NETT_CALCULATION:
                $this->result->takeHomePay = $this->result->earnings->nett + $this->employee->earnings->holidayAllowance + $this->employee->bonus->getSum();
                break;
            // Pajak ditanggung oleh karyawan
            case self::GROSS_CALCULATION:
                $this->result->takeHomePay = $this->result->earnings->nett + $this->employee->bonus->getSum() - $this->result->taxable->liability->amount;
                $this->result->deductions->offsetSet('pph26Tax', $this->result->taxable->liability->amount);
                break;
            // Pajak ditanggung oleh perusahaan sebagai tunjangan pajak.
            case self::GROSS_UP_CALCULATION:
                $this->result->takeHomePay = $this->result->earnings->nett + $this->employee->bonus->getSum();
                $this->result->allowances->offsetSet('pph26Tax', $this->result->taxable->liability->amount);
                break;
        }

        return $this->result;
    }
}
