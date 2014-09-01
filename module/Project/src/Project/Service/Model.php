<?php

namespace Project\Service;

use Project\Entity\Project as Project;

class Model 
{
    function payback(Project $project) {
        // ** deprecated **
        //$years = $project->getModel();
		//if (($years<3) || ($years>10)) { $years = 5; }
        
        // Note: we should always find 10 year model (12 year max)
        $years = 12;
        
        //calculate funding options
        $financing = false;
        if (!empty($project->getClient()->getFinanceStatus()) && ($project->getClient()->getFinanceStatus()->getFinanceStatusId()>1)) {
            if (($project->getFinanceYears()->getFinanceYearsId() >0 )) {
                if (!empty($project->getFinanceProvider())){
                    $financing = true;
                }
            }
        } 
        
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        
        $forecast = array();
		$overview = array();
		$totals = array();
        
        $qb
            ->select('s.label, s.cpu, s.ppu, s.ippu, s.quantity, s.hours, s.legacyWatts, s.legacyQuantity, s.legacyMcpu, s.lux, s.occupancy, s.locked, s.systemId, '
                    . 'sp.spaceId, sp.name, '
                    . 'b.name, b.buildingId,'
                    . 'ba.postcode,'
                    . 'p.model, p.pwr, p.eca, p.description, p.productId, p.ibppu, p.mcd,'
                    . 'pt.typeId AS productType ')
            ->from('Space\Entity\System', 's')
            ->join('s.space', 'sp')
            ->join('sp.building', 'b')
            ->join('b.address', 'ba')
            ->join('s.product', 'p')
            ->join('p.brand', 'pb')
            ->join('p.type', 'pt')
            ->where('sp.project=?1')
            ->setParameter(1, $project->getProjectId())
            ->add('orderBy', 's.space ASC');

        
        $query  = $qb->getQuery();      
        $result = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        
        $totals = array (
            'legacyMaintenance' => 0,
			'currentElecConsumption' => 0,
			'ledElecConsumption' => 0,
			'co2emmissionreduction' => 0,
			'elec_sav_ach' => 0,
            'productcost' => 0,
            'productcost_base' => 0,
			'price_base' => 0,
			'price' => 0,
			'priceeca' => 0,
            'IBP' => 0,
            'prelim' => 0,
            'overhead' => 0,
            'management' => 0,
            'fee' => 0,
            
            'price_installation' => 0,
            'price_delivery' => 0,
            'price_product' => 0,
            'price_access' => 0,
            
            'kwhSave' => 0,
        );
        
        
        
        $discount = $project->getMcd();
        
        $spaces = array();
        foreach ($result as $obj) {
            if (empty($spaces[$obj['spaceId']])) {
                $spaces[$obj['spaceId']] = true;
            }
            
            $led = ($obj['productType'] == 1); // type 1 is an LED
            $installation = ($obj['productType'] == 100); // type 100 is an installation product
            $delivery = ($obj['productType'] == 101); // type 101 is a delivery product
            $access = ($obj['productType'] == 102); // type 102 is an access product
            
            // calculate price
            $priceIncDiscount = round($obj['ppu'] * (1-($discount * $obj['mcd'])),2);
            $price = round(($obj['quantity'] * $priceIncDiscount),2);
            
            if ($led && $project->getIbp()) {
                $totals['IBP']+=($obj['ibppu'] * $obj['quantity']);
            }
            
            // calculate power savings (if applicable)
            if ($installation) {
                $totals['price_installation']+=$price;
            } elseif ($delivery) {
                $totals['price_delivery']+=$price;
            } elseif ($access) {
                $totals['price_access']+=$price;
            } else {
                $pwrSaveLeg = ($obj['legacyWatts']*$obj['legacyQuantity']);
                $pwrSaveLed = ($obj['quantity']*$obj['pwr']) * (1-$obj['lux']) * (1 - $obj['occupancy']);
                $pwrSave = (!$led||($obj['legacyWatts']==0))?0:((($pwrSaveLeg-$pwrSaveLed)/($obj['legacyWatts'] * $obj['legacyQuantity'])) * 100);
                $kwHSave = (!$led||($obj['legacyWatts']==0))?0:((($pwrSaveLeg-$pwrSaveLed)/1000) * $obj['hours'] * 52);

                $currentElecConsumption = round((($obj['legacyQuantity'] * $obj['hours'] * $obj['legacyWatts'] * 52)/1000) * $project->getFuelTariff(),2);
                $ledElecConsumption = round(((100-$pwrSave)/100) * $currentElecConsumption,2);
                $elec_sav_ach = round($currentElecConsumption - $ledElecConsumption, 2);
            
                // calculate co2 savings
                $co2emmissionreduction = round((($elec_sav_ach / $project->getFuelTariff()) * $project->getCo2()) / 1000,2);

                // calculate maintenance cost
                $legacyMaintenance = round($obj['legacyQuantity'] * $obj['legacyMcpu'],2);
                
                // shift totals as per iteration
                $totals['elec_sav_ach']+=$elec_sav_ach;
                $totals['currentElecConsumption']+=$currentElecConsumption;
                $totals['ledElecConsumption']+=$ledElecConsumption;
                $totals['co2emmissionreduction']+= $co2emmissionreduction;
                $totals['legacyMaintenance']+=$legacyMaintenance;
                if (!empty($obj['eca'])) {
                    $totals['priceeca']+=$price;
                }
                $totals['price_product']+=$price;
                $totals['productcost']+=($obj['cpu'] * $obj['quantity']);
                $totals['kwhSave']+=$kwHSave;
            }
            

            
            // shift totals as per iteration
            $totals['price']+=$price;
        }
        
        // adjust legacy maintenance if required
        if($project->getMaintenance()>0) {
            $totals['legacyMaintenance'] = $project->getMaintenance();
        }

        $csav = 0;
        $carbon = 0;
        
        // work out additional fee (if applicable)
        $totals['prelim'] = round($totals['price'] * $project->getFactorPrelim(),2);
        $totals['overhead'] = round(($totals['price'] + $totals['prelim']) * $project->getFactorOverhead(),2);
        $totals['management'] = round(($totals['price'] + $totals['prelim'] + $totals['overhead']) * $project->getFactorManagement(),2);
        $totals['fee'] = round($totals['prelim'] + $totals['overhead'] + $totals['management'],2);

        // total cost
        $total_cost = round($totals['price'] + $totals['fee'] + $totals['IBP'],2);
        
        // cost of financing
        $financing_unsupported=false;
        if ($financing) {
            $finance_data = array();
            $finance_data['amount'] = $total_cost;
            
            $qb2 = $em->createQueryBuilder();
            $qb2
                ->select('f ')
                ->from('Project\Entity\Finance', 'f')
                ->where('f.financeStatus=?1 AND f.financeYears=?2 AND f.min <= ?3 AND f.max >= ?3')
                ->setParameter(1, $project->getClient()->getfinanceStatus()->getFinanceStatusId())
                ->setParameter(2, $project->getFinanceYears()->getFinanceYearsId())
                ->setParameter(3, $total_cost);


            $query  = $qb2->getQuery();      
            $result = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
            
            if (empty($result)) {
                $financing = false;
                $financing_unsupported = true;
            } else {
                $ff = array_shift($result);
                $finance_data['repayments'] = round($finance_data['amount'] * (float)$ff['factor'] * 4,0);
                $finance_data['annualrate'] = round((((($finance_data['repayments'] *  $project->getFinanceYears()->getFinanceYearsId()) - $finance_data['amount'])/$finance_data['amount']) / $project->getFinanceYears()->getFinanceYearsId())*100,2);
            }
        }
        
        // calculate eca saving & carbon allowance
        $eca = $totals['priceeca'] * $project->getEca(); // new method based on individual light eca compatibility
        $callow = $project->getCarbon();
        
        $payback = $financing?0:-($totals['price'] + $totals['fee'] + $totals['IBP']);
        $payback_eca = $financing?$eca:-($totals['price'] + $totals['fee'] + $totals['IBP'] - $eca);

        $finance_avg_benefit = 0;
        $finance_avg_repay = 0;
        $finance_avg_netbenefit = 0;
        
        
        $payback_year = 0;
        for($i=1; $i<=$years; $i++) {
            $legsp = ($totals['currentElecConsumption'] * pow(1 + $project->getEpi(),$i-1));
            $ledsp = ($totals['ledElecConsumption'] * pow(1 + $project->getEpi(),$i-1));
            $cam = ($totals['legacyMaintenance'] * pow(1+$project->getRpi(),$i-1));
            $carbon+= $totals['co2emmissionreduction'];

            $cost_of_financing = ($financing)?(($project->getFinanceYears()->getFinanceYearsId() >= $i)?$finance_data['repayments']:0):0;
            $cash_benefit = round(($cam + ($legsp-$ledsp)) - $cost_of_financing,2);

            $csav+=round($cam + ($legsp-$ledsp),2);

            $payback+=($cam + ($legsp-$ledsp));

            $payback_eca+=($cam + ($legsp-$ledsp) + ($callow * $totals['co2emmissionreduction']));

            if ($project->getFinanceYears()->getFinanceYearsId() >= $i) {
                $finance_avg_benefit+=round($cam + ($legsp-$ledsp),2);
                $finance_avg_repay+=$cost_of_financing;
                $finance_avg_netbenefit+=$cash_benefit;
            }

            $payback-=$cost_of_financing;
            $payback_eca-=$cost_of_financing;

            $forecast[$i] = array (
                round($legsp,2),
                round($ledsp,2),
                round($legsp-$ledsp,2), // electricity saving 
                round($cam,2),
                round($cam + ($legsp-$ledsp),2),
                round($csav,2),
                round(($legsp-$ledsp)/12,2),
                round($totals['co2emmissionreduction'],2),
                round($payback,2),
                round($payback_eca,2),
                round(($callow * $totals['co2emmissionreduction']),2),
                $cost_of_financing,
                $cash_benefit,
            );

            if ($payback_eca>0) {
                $payback_year= $i;
            }
        }
        
        $carballow = ($callow * $carbon);
			

        $figures = array(
            'saving' => round($csav,2),
            'cost_maintenance' => round($totals['legacyMaintenance'],2),
            'cost_install' => $totals['price_installation'], 
            'cost_delivery' => round($totals['price_delivery'],2), 
            'cost_led' => round($totals['price_product'],2), // TO DO
            'margin' => ($totals['price_product']>0)?round((1-($totals['productcost']/$totals['price_product']))*100,2):0,
            'cost' => $total_cost,
            'costvat' => round(($total_cost * 1.2),2),
            'costvateca' => round(($total_cost * 1.2)-$eca,2),
            'vat'=> round(($total_cost * 0.2),2),
            'costeca' => round($total_cost-$eca,2),
            'cost_prelim' => $totals['prelim'],
            'cost_overheads' => $totals['overhead'],
            'cost_management' => $totals['management'],// $total_fee,
            'cost_access' => $totals['price_access'],
            'cost_ibp'=>$totals['IBP'],
            'profit' => round($payback,2),
            'profiteca' => round($payback_eca,2),
            'carbon' => round($carbon,2),
            'eca' => round($eca,2),
            'eca_eligible' => round($totals['priceeca'],2),
            'eca_ineligible' => round($totals['price']-$totals['priceeca'],2),
            'carbonallowance' => $carballow,
            'finance_amount' => ($financing?($finance_data['repayments'] * $project->getFinanceYears()->getFinanceYearsId()):0),
            'finance_years' => ($financing?$project->getFinanceYears()->getFinanceYearsId():0),
            'finance_annual_repayment' => ($financing?$finance_data['repayments']:0),
            'finance_annual_rate' => ($financing?$finance_data['annualrate']:0),
            'finance_avg_benefit' => ($financing?round($finance_avg_benefit/$project->getFinanceYears()->getFinanceYearsId(),2):0),
            'finance_avg_repay' => ($financing?round($finance_avg_repay/$project->getFinanceYears()->getFinanceYearsId(),2):0),
            'finance_avg_netbenefit' => ($financing?round($finance_avg_netbenefit/$project->getFinanceYears()->getFinanceYearsId(),2):0),
            'finance_netbenefit' => ($financing?round($finance_avg_netbenefit,2):0),
            'space_count' => count($spaces),
            'payback_year' => $payback_year,
            'kwhYear' => $totals['kwhSave'],
        );

        if ($financing) {
            $figures['finance_exceeds'] = (($project->getClient()->getFund() < $total_cost)?1:0);
        }

        if ($financing_unsupported) {
            $figures['finance_unsupported'] = 1;
        }
        /**/
        
        return array (
            'figures' => $figures,
            'forecast' => $forecast
        );
    }
    
    
    /**
     * calculate space performance
     * @param \Project\Entity\Project $project
     * @param array $args
     * @return type
     */
    function spaceBreakdown(Project $project, array $args = array()) {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        
		$breakdown = array();
        
        
        $qb
            ->select('s.label, s.cpu, s.ppu, s.ippu, s.quantity, s.hours, s.legacyWatts, s.legacyQuantity, s.legacyMcpu, s.lux, s.occupancy, s.locked, s.systemId, '
                    . 'sp.spaceId, sp.name AS sName, '
                    . 'b.name AS bName, b.buildingId,'
                    . 'ba.postcode,'
                    . 'p.model, p.pwr, p.eca, p.description, p.productId, p.ibppu, p.mcd,'
                    . 'pt.typeId AS productType, '
                    . 'l.legacyId, l.description '
                    )
            ->from('Space\Entity\System', 's')
            ->join('s.space', 'sp')
            ->join('sp.building', 'b')
            ->join('b.address', 'ba')
            ->join('s.product', 'p')
            ->join('p.brand', 'pb')
            ->join('p.type', 'pt')
            ->leftJoin('s.legacy', 'l')
            ->where('sp.project=?1')
            ->setParameter(1, $project->getProjectId())
            ->add('orderBy', 's.space ASC');

        
        $query  = $qb->getQuery();      
        $result = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        
        
        $discount = $project->getMcd();
        
        foreach ($result as $obj) {
            $led = ($obj['productType'] == 1); // type 1 is an LED
            $installation = ($obj['productType'] == 100); // type 100 is an installation product
            $delivery = ($obj['productType'] == 101); // type 101 is a delivery product
            $access = ($obj['productType'] == 102); // type 102 is an access product
            
            if (!isset($breakdown[$obj['buildingId']])) {
                $breakdown [$obj['buildingId']] = array (
                    'name' => $obj['bName'],
                    'postcode' => $obj['postcode'],
                    'spaces' => array ()
                );
            }
            
            if (!isset($breakdown[$obj['buildingId']] ['spaces'] [$obj['spaceId']])) {
                $breakdown [$obj['buildingId']] ['spaces'] [$obj['spaceId']] = array (
                    'name' => $obj['sName'],
                    'products' => array ()
                );
            }
            
            
            // calculate price
            $priceIncDiscount = round($obj['ppu'] * (1-($discount * $obj['mcd'])),2);
            $price = round(($obj['quantity'] * $priceIncDiscount),2);
            
            
            // calculate power savings (if applicable)
            if ($installation || $delivery || $access) {
                $breakdown[$obj['buildingId']] ['spaces'] [$obj['spaceId']] ['products'][$obj['systemId']] = array(
					$price,
                    $price,
                    $obj['productType'],
                    $obj['productId'],
                    $obj['model'],
                    $obj['quantity'],
                    0,
                    0,
                    null,
                    null,
                    null,
					0,
					0,
					0,
					0,
                    0,
				);/**/
            } else {
                $pwrSaveLeg = ($obj['legacyWatts']*$obj['legacyQuantity']);
                $pwrSaveLed = ($obj['quantity']*$obj['pwr']) * (1-$obj['lux']) * (1 - $obj['occupancy']);
                $pwrSave = (!$led||($obj['legacyWatts']==0))?0:((($pwrSaveLeg-$pwrSaveLed)/($obj['legacyWatts'] * $obj['legacyQuantity'])) * 100);
                $kwHSave = (!$led||($obj['legacyWatts']==0))?0:((($pwrSaveLeg-$pwrSaveLed)/1000) * $obj['hours'] * 52);

                $currentElecConsumption = round((($obj['legacyQuantity'] * $obj['hours'] * $obj['legacyWatts'] * 52)/1000) * $project->getFuelTariff(),2);
                $ledElecConsumption = round(((100-$pwrSave)/100) * $currentElecConsumption,2);
                $elec_sav_ach = round($currentElecConsumption - $ledElecConsumption, 2);
            
                // calculate co2 savings
                $co2emmissionreduction = round((($elec_sav_ach / $project->getFuelTariff()) * $project->getCo2()) / 1000,2);

                // calculate maintenance cost
                $legacyMaintenance = round($obj['legacyQuantity'] * $obj['legacyMcpu'],2);
                
                // add line data
                $breakdown[$obj['buildingId']] ['spaces'] [$obj['spaceId']] ['products'] [$obj['systemId']] = array(
					$priceIncDiscount,
                    $price,
                    $obj['productType'],
                    $obj['productId'],
                    $obj['model'],
                    $obj['quantity'],
                    $obj['hours'],
                    $obj['pwr'],
                    $obj['description'],
                    $obj['legacyQuantity'],
                    $obj['legacyWatts'],
					$legacyMaintenance,
					round($pwrSave,2),
					$elec_sav_ach,
					$co2emmissionreduction,
                    $kwHSave,
				);/**/
            }
            
        }

        //echo '<pre>', print_r($breakdown, true), '</pre>';die();
        
        /**/
        
        return $breakdown;
    }
    
    
    // factory involkable methods
    protected $em;
    
    function setEntityManager(\Doctrine\ORM\EntityManager $em) {
        $this->em = $em;
    }
    
    public function getEntityManager() {
        return $this->em;
    }


    
}

