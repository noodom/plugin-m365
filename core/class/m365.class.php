<?php

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class m365 extends eqLogic {
	/*
	TODO :
        - ajouter des alertes : différence(min/max) voltage cellules importante, alerte de un (plusieurs) niveau(x) de batterie
        - ajouter un widget personnalisé
	*/

    /*     * *************************Attributs****************************** */

    /*     * ***********************Methode static*************************** */
	public static function bleachange($_option) {
		log::add('m365','debug','bleachange::changement d\'état de présence : '.$_option['value'].' (id '.$_option['m365_id'].')');
		$m365 = m365::byId($_option['m365_id']);
		$m365->updateStatus();		
	}

	public function updateStatus() {
		console.log('updateStatus');
		$confblea = $this->getConfiguration('bleactrl');
		$eq = eqLogic::byId(str_replace('#', '', $confblea));
		$cmd = $eq->getCmd(null, 'Present');
		$val = $cmd->execCmd();
		$presence = $this->getCmd(null, 'presence');

		if ($val == 0) {
			log::add('m365','info','updateStatus:: Trottinette '.$this->getId().' absente');
			$presence->event('Absente');
		}	
		else if ($val == 1) {
			log::add('m365','info','updateStatus:: Trottinette '.$this->getId().' présente');
			$presence->event('Présente');
			// récupération des informations de la trottinette pour mise à jour des données du plugin
			$this->updateDataM365();
		}	
	}

	public function updateDataM365() {
		log::add('m365','info','lancement de la mise à jour des données de la trottinette d\'id '.$this->getId());
		// mise à jour de la date de récupération
		$maj = $this->getCmd(null, 'update_time');
		$maj->event (date("d-m-Y H:i:s"));
		
		// récupération des informations de la trottinette pour mise à jour du fichier de données 
		$this->saveJsonFile();

		sleep(10);

		// traitement du fichier m365.json pour mise à jour des données du plugin
		$this->parseJsonFile();

		// vérification des alertes
		$this->verificationAlerte();
	}

	/*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom
      public static function cron() {

      }
     */

	// construit le nom du fichier de configuration de l équipement courant à partir de son id
	public function getConfigFilename() {
		// création du répertoire /data si non présent
		$data_path = dirname(__FILE__) . '/../../data';
		if (!file_exists($data_path)) {
			exec('mkdir ' . $data_path . ' && chmod 775 -R ' . $data_path . ' && chown -R www-data:www-data ' . $data_path);
		}

		$cfgFilename = $data_path . '/'. 'm365_'.$this->getId().'.json';
		log::add('m365','debug','fichier de sauvegarde : '.$cfgFilename);
		return $cfgFilename;
	}

	public function getMacAdress() {
		log::add('m365','debug','récupération de l\'adresse mac');
		$bleacontrol = $this->getConfiguration('bleactrl');
		if ($bleacontrol == '') {
			log::add('m365','info','équipement blea vide');
			return;
		}
		$eq = eqLogic::byId(str_replace('#', '', $bleacontrol));
		if (!is_object($eq)) {
			throw new Exception(__('Equipement blea ' . $bleacontrol . ' inconnu', __FILE__));
		}
		else {
			log::add('m365','debug','Equipement blea '.$bleacontrol. ' trouvé');
		}
		
		$macAdress = $eq->getLogicalId();
		log::add('m365','debug','adresse mac du blea '.$bleacontrol. ' : ' . $macAdress);
		return $macAdress;
	}

	public function saveJsonFile() {
		log::add('m365','info','sauvegarde du fichier json m365');
		$configFile = $this->getConfigFilename();
		$macAdress = $this->getMacAdress();
		$autoReconnect = $this->getConfiguration('autoReconnect','0');
		log::add('m365', 'debug', 'macAdress : ' . $macAdress . ', configFile : ' . $configFile . ' , autoReconnect : ' . $autoReconnect);

		$data_path = dirname(__FILE__) . '/../../resources';
		$shFilename = $data_path . '/'. 'jsonGeneration.sh';
		$cmd = system::getCmdSudo() . ' /bin/bash '.$shFilename.' '.$macAdress . ' ' . $configFile . ' ' . $autoReconnect . ' >> ' . log::getPathToLog('m365') . ' 2>&1';
		
		log::add('m365', 'info', $cmd);
		shell_exec($cmd);
	}

	public function parseJsonFile() {
		$configFile = $this->getConfigFilename();
		log::add('m365','info','traitement des données de la trottinette');
		$json = file_get_contents($configFile);

		$arr = json_decode($json, true);

		$battery_capacity = $arr['battery_capacity'];
		$cmd = $this->getCmd(null, 'battery_capacity');
		$cmd->event($battery_capacity.' Ah');
		
		$battery_current = $arr['battery_current'];
		$cmd = $this->getCmd(null, 'battery_current');
		$cmd->event($battery_current.' A');

		$battery_percent = $arr['battery_percent'];
		$cmd = $this->getCmd(null, 'battery_percent');
		$cmd->event($battery_percent.' %');

		$battery_temperature_1 = $arr['battery_temperature_1'];
		$cmd = $this->getCmd(null, 'battery_temperature_1');
		$cmd->event($battery_temperature_1.' °');

		$battery_temperature_2 = $arr['battery_temperature_2'];
		$cmd = $this->getCmd(null, 'battery_temperature_2');
		$cmd->event($battery_temperature_2.' °');

		$battery_voltage = $arr['battery_voltage'];
		$cmd = $this->getCmd(null, 'battery_voltage');
		$cmd->event($battery_voltage.' V');

		/*    "cell_voltages": [36.95, 36.98, 37.01, 36.95, 36.98, 37.03, 37.04, 37.06, 37.03, 36.97], */
		$cell_voltages = $arr['cell_voltages'];
		$cmd = $this->getCmd(null, 'cell_voltages');
		$cmd->event(implode("/",$cell_voltages));

		$distance_left_km = $arr['distance_left_km'];
		$cmd = $this->getCmd(null, 'distance_left_km');
		$cmd->event($distance_left_km.' kms');

		$frame_temperature = $arr['frame_temperature'];
		$cmd = $this->getCmd(null, 'frame_temperature');
		$cmd->event($frame_temperature.' °');

		$is_cruise_on = ($arr['is_cruise_on']=='true')?1:0;
		$cmd = $this->getCmd(null, 'is_cruise_on');
		$cmd->event($is_cruise_on);

		$is_lock_on = ($arr['is_lock_on']=='true')?1:0;
		$cmd = $this->getCmd(null, 'is_lock_on');
		$cmd->event($is_lock_on);

		$is_tail_light_on = ($arr['is_tail_light_on']=='true')?1:0;
		$cmd = $this->getCmd(null, 'is_tail_light_on');
		$cmd->event($is_tail_light_on);

		$kers_mode = ($arr['kers_mode']==0)?'faible':(($arr['kers_mode']==1)?'moyen':'fort');
		$cmd = $this->getCmd(null, 'kers_mode');
		$cmd->event($kers_mode);

		$odometer_km = $arr['odometer_km'];
		$cmd = $this->getCmd(null, 'odometer_km');
		$cmd->event($odometer_km.' kms');

		$pin = $arr['pin'];
		$cmd = $this->getCmd(null, 'pin');
		$cmd->event($pin);

		$serial = $arr['serial'];
		$cmd = $this->getCmd(null, 'serial');
		$cmd->event($serial);

		$speed_average_kmh = $arr['speed_average_kmh'];
		$cmd = $this->getCmd(null, 'speed_average_kmh');
		$cmd->event($speed_average_kmh.' km/h');

		$speed_kmh = $arr['speed_kmh'];
		$cmd = $this->getCmd(null, 'speed_kmh');
		$cmd->event($speed_kmh.' km/h');

		$trip_distance_m = $arr['trip_distance_m'];
		$cmd = $this->getCmd(null, 'trip_distance_m');
		$cmd->event($trip_distance_m.' m');

		$uptime_s = $arr['uptime_s'];
		$cmd = $this->getCmd(null, 'uptime_s');
		$cmd->event($uptime_s.' m');

		$version = $arr['version'];
		$cmd = $this->getCmd(null, 'version');
		$cmd->event($version);

		// logs des données récupérées
		log::add('m365','debug','$battery_capacity : '.$battery_capacity);
		log::add('m365','debug','$battery_current : '.$battery_current);
		log::add('m365','debug','$battery_percent : '.$battery_percent);
		log::add('m365','debug','$battery_temperature_1 : '.$battery_temperature_1);
		log::add('m365','debug','$battery_temperature_2 : '.$battery_temperature_2);
		log::add('m365','debug','$battery_voltage : '.$battery_voltage);
		log::add('m365','debug','$cell_voltages : '.implode("/",$cell_voltages));
		log::add('m365','debug','$distance_left_km : '.$distance_left_km);
		log::add('m365','debug','$frame_temperature : '.$frame_temperature);
		log::add('m365','debug','$is_cruise_on : '.$is_cruise_on);
		log::add('m365','debug','$is_lock_on : '.$is_lock_on);
		log::add('m365','debug','$is_tail_light_on : '.$is_tail_light_on);
		log::add('m365','debug','$kers_mode : '.$kers_mode);
		log::add('m365','debug','$odometer_km : '.$odometer_km);
		log::add('m365','debug','$pin : '.$pin);
		log::add('m365','debug','$serial : '.$serial);
		log::add('m365','debug','$speed_average_kmh : '.$speed_average_kmh);
		log::add('m365','debug','$speed_average_kmh : '.$speed_kmh);
		log::add('m365','debug','$trip_distance_m : '.$trip_distance_m);
		log::add('m365','debug','$uptime_s : '.$uptime_s);
		log::add('m365','debug','$version : '.$version);
	}


	public function verificationAlerte() {
		// récupération de la valeur courante de la batterie
		$battery_percent = $arr['battery_percent'];
		$cmd = $this->getCmd(null, 'battery_percent');
		$batteryPercent = $cmd->execCmd();	

		// récupération du seuil d'alerte
		$batteryLevelAlert = $this->getConfiguration('pourcentageAlerte');

		if ($batteryPercent < $batteryLevelAlert) {
			$messageAlert = ($this->getConfiguration('messageAlerte') == '')?'Attention, batterie faible (< ' . $batteryLevelAlert . ')':$messageAlert;
			log::add('m365','debug', $messageAlert);
			// appel des actions d'alerte enregistrées dans l'équipement
			$this->actionsComplementaires('addActionOnAlert');	
		}

	}

	public function actionsComplementaires($type) {
		console.log('actions complémentaires');
		if (count($this->getConfiguration($type)) > 0) {
			foreach ($this->getConfiguration($type) as $action) {
				try {
					$cmd = cmd::byId(str_replace('#', '', $action['cmd']));
					if (is_object($cmd) && $this->getId() == $cmd->getEqLogic_id()) {
						continue;
					}
					$options = array();
					if (isset($action['options'])) {
						$options = $action['options'];
					}
					scenarioExpression::createAndExec('action', $action['cmd'], $options);
				} catch (Exception $e) {
					log::add('m365', 'error', $this->getHumanName() . __(' : Erreur lors de l\'éxecution de ', __FILE__) . $type . ' : ' . $action['cmd'] . __('. Détails : ', __FILE__) . $e->getMessage());
				}
			}
		}
	}

	public static function cronHourly($_eqLogic_id = null) {
		console.log('cronHourly');
		if ($_eqLogic_id == null) { // cron sur tous les équipements du plugin
			$eqLogics = self::byType('m365', true);
		} else { // cron sur l'équipement en paramètre
			$eqLogics = array(self::byId($_eqLogic_id));
		}		  
	
		foreach ($eqLogics as $m365) {
			if ($m365->getIsEnable() == 1) {
				$cmd = $m365->getCmd(null, 'refresh');
				if (!is_object($cmd)) {
				  continue;
				}

				log::add('m365', 'debug', 'cronHourly::appel refresh');
				$cmd->execCmd();
			}
		}
	}

    /*     * *********************Méthodes d'instance************************* */

    public function preInsert() {
    }

    public function postInsert() {        
    }

    public function preSave() {
		$this->setDisplay("width","300px");
		$this->setDisplay("showNameOndashboard",1);
    }

    public function postSave() {
		// presence
		$pres = $this->getCmd(null, 'presence');
		if (!is_object($pres)) {
			$pres = new m365Cmd();
			$pres->setName(__('Présence', __FILE__));
		}
		$pres->setLogicalId('presence');
		$pres->setEqLogic_id($this->getId());
		$pres->setType('info');
		$pres->setTemplate('dashboard','default');
		$pres->setDisplay("showNameOndashboard",1);
		$pres->setSubType('string');
		$pres->setOrder(1);
		$pres->save();	

		// battery_capacity
		$battery_capacity = $this->getCmd(null, 'battery_capacity');
		if (!is_object($battery_capacity)) {
			$battery_capacity = new m365Cmd();
			$battery_capacity->setName(__('Capacité Batterie', __FILE__));
		}
		$battery_capacity->setLogicalId('battery_capacity');
		$battery_capacity->setEqLogic_id($this->getId());
		$battery_capacity->setType('info');
		$battery_capacity->setTemplate('dashboard','default');
		$battery_capacity->setDisplay("showNameOndashboard",1);
		$battery_capacity->setSubType('string');
		$battery_capacity->setOrder(2);
		$battery_capacity->save();	

		// battery_current
		$battery_current = $this->getCmd(null, 'battery_current');
		if (!is_object($battery_current)) {
			$battery_current = new m365Cmd();
			$battery_current->setName(__('Batterie courante', __FILE__));
		}
		$battery_current->setLogicalId('battery_current');
		$battery_current->setEqLogic_id($this->getId());
		$battery_current->setType('info');
		$battery_current->setTemplate('dashboard','default');
		$battery_current->setDisplay("showNameOndashboard",1);
		$battery_current->setSubType('string');
		$battery_current->setOrder(3);
		$battery_current->save();	

		// battery_percent
		$battery_percent = $this->getCmd(null, 'battery_percent');
		if (!is_object($battery_percent)) {
			$battery_percent = new m365Cmd();
			$battery_percent->setName(__('% Batterie', __FILE__));
		}
		$battery_percent->setLogicalId('battery_percent');
		$battery_percent->setEqLogic_id($this->getId());
		$battery_percent->setType('info');
		$battery_percent->setTemplate('dashboard','default');
		$battery_percent->setDisplay("showNameOndashboard",1);
		$battery_percent->setSubType('string');
		$battery_percent->setOrder(4);
		$battery_percent->save();	

		// battery_temperature_1
		$battery_temperature_1 = $this->getCmd(null, 'battery_temperature_1');
		if (!is_object($battery_temperature_1)) {
			$battery_temperature_1 = new m365Cmd();
			$battery_temperature_1->setName(__('temp. batterie 1', __FILE__));
		}
		$battery_temperature_1->setLogicalId('battery_temperature_1');
		$battery_temperature_1->setEqLogic_id($this->getId());
		$battery_temperature_1->setType('info');
		$battery_temperature_1->setTemplate('dashboard','default');
		$battery_temperature_1->setDisplay("showNameOndashboard",1);
		$battery_temperature_1->setSubType('string');
		$battery_temperature_1->setOrder(5);
		$battery_temperature_1->save();	

		// battery_temperature_2
		$battery_temperature_2 = $this->getCmd(null, 'battery_temperature_2');
		if (!is_object($battery_temperature_2)) {
			$battery_temperature_2 = new m365Cmd();
			$battery_temperature_2->setName(__('temp. batterie 2', __FILE__));
		}
		$battery_temperature_2->setLogicalId('battery_temperature_2');
		$battery_temperature_2->setEqLogic_id($this->getId());
		$battery_temperature_2->setType('info');
		$battery_temperature_2->setTemplate('dashboard','default');
		$battery_temperature_2->setDisplay("showNameOndashboard",1);
		$battery_temperature_2->setSubType('string');
		$battery_temperature_2->setOrder(6);
		$battery_temperature_2->save();	

		// battery_voltage
		$battery_voltage = $this->getCmd(null, 'battery_voltage');
		if (!is_object($battery_voltage)) {
			$battery_voltage = new m365Cmd();
			$battery_voltage->setName(__('voltage batterie', __FILE__));
		}
		$battery_voltage->setLogicalId('battery_voltage');
		$battery_voltage->setEqLogic_id($this->getId());
		$battery_voltage->setType('info');
		$battery_voltage->setTemplate('dashboard','default');
		$battery_voltage->setDisplay("showNameOndashboard",1);
		$battery_voltage->setSubType('string');
		$battery_voltage->setOrder(7);
		$battery_voltage->save();	

		// cell_voltages
		$cell_voltages = $this->getCmd(null, 'cell_voltages');
		if (!is_object($cell_voltages)) {
			$cell_voltages = new m365Cmd();
			$cell_voltages->setName(__('voltage cellules', __FILE__));
		}
		$cell_voltages->setLogicalId('cell_voltages');
		$cell_voltages->setEqLogic_id($this->getId());
		$cell_voltages->setType('info');
		$cell_voltages->setTemplate('dashboard','default');
		$cell_voltages->setDisplay("showNameOndashboard",1);
		$cell_voltages->setSubType('string');
		$cell_voltages->setOrder(8);
		$cell_voltages->save();	

		// distance_left_km
		$distance_left_km = $this->getCmd(null, 'distance_left_km');
		if (!is_object($distance_left_km)) {
			$distance_left_km = new m365Cmd();
			$distance_left_km->setName(__('kms restants', __FILE__));
		}
		$distance_left_km->setLogicalId('distance_left_km');
		$distance_left_km->setEqLogic_id($this->getId());
		$distance_left_km->setType('info');
		$distance_left_km->setTemplate('dashboard','default');
		$distance_left_km->setDisplay("showNameOndashboard",1);
		$distance_left_km->setSubType('string');
		$distance_left_km->setOrder(9);
		$distance_left_km->save();

		// frame_temperature
		$frame_temperature = $this->getCmd(null, 'frame_temperature');
		if (!is_object($frame_temperature)) {
			$frame_temperature = new m365Cmd();
			$frame_temperature->setName(__('frame température', __FILE__));
		}
		$frame_temperature->setLogicalId('frame_temperature');
		$frame_temperature->setEqLogic_id($this->getId());
		$frame_temperature->setType('info');
		$frame_temperature->setTemplate('dashboard','default');
		$frame_temperature->setDisplay("showNameOndashboard",1);
		$frame_temperature->setSubType('string');
		$frame_temperature->setOrder(10);
		$frame_temperature->setIsVisible(0);
		$frame_temperature->save();	

		// is_cruise_on
		$is_cruise_on = $this->getCmd(null, 'is_cruise_on');
		if (!is_object($is_cruise_on)) {
			$is_cruise_on = new m365Cmd();
			$is_cruise_on->setName(__('Cruise', __FILE__));
		}
		$is_cruise_on->setLogicalId('is_cruise_on');
		$is_cruise_on->setEqLogic_id($this->getId());
		$is_cruise_on->setType('info');
		$is_cruise_on->setTemplate('dashboard','default');
		$is_cruise_on->setDisplay("showNameOndashboard",1);
		$is_cruise_on->setSubType('binary');
		$is_cruise_on->setOrder(11);
		$is_cruise_on->save();	

		// is_lock_on
		$is_lock_on = $this->getCmd(null, 'is_lock_on');
		if (!is_object($is_lock_on)) {
			$is_lock_on = new m365Cmd();
			$is_lock_on->setName(__('Lock', __FILE__));
		}
		$is_lock_on->setLogicalId('is_lock_on');
		$is_lock_on->setEqLogic_id($this->getId());
		$is_lock_on->setType('info');
		$is_lock_on->setTemplate('dashboard','default');
		$is_lock_on->setDisplay("showNameOndashboard",1);
		$is_lock_on->setSubType('binary');
		$is_lock_on->setOrder(12);
		$is_lock_on->save();	

		// is_tail_light_on
		$is_tail_light_on = $this->getCmd(null, 'is_tail_light_on');
		if (!is_object($is_tail_light_on)) {
			$is_tail_light_on = new m365Cmd();
			$is_tail_light_on->setName(__('Lumière', __FILE__));
		}
		$is_tail_light_on->setLogicalId('is_tail_light_on');
		$is_tail_light_on->setEqLogic_id($this->getId());
		$is_tail_light_on->setType('info');
		$is_tail_light_on->setTemplate('dashboard','default');
		$is_tail_light_on->setDisplay("showNameOndashboard",1);
		$is_tail_light_on->setSubType('binary');
		$is_tail_light_on->setOrder(13);
		$is_tail_light_on->save();	

		// kers_mode
		$kers_mode = $this->getCmd(null, 'kers_mode');
		if (!is_object($kers_mode)) {
			$kers_mode = new m365Cmd();
			$kers_mode->setName(__('Kers', __FILE__));
		}
		$kers_mode->setLogicalId('kers_mode');
		$kers_mode->setEqLogic_id($this->getId());
		$kers_mode->setType('info');
		$kers_mode->setTemplate('dashboard','default');
		$kers_mode->setDisplay("showNameOndashboard",1);
		$kers_mode->setSubType('string');
		$kers_mode->setOrder(14);
		$kers_mode->save();	

		// odometer_km
		$odometer_km = $this->getCmd(null, 'odometer_km');
		if (!is_object($odometer_km)) {
			$odometer_km = new m365Cmd();
			$odometer_km->setName(__('kms total', __FILE__));
		}
		$odometer_km->setLogicalId('odometer_km');
		$odometer_km->setEqLogic_id($this->getId());
		$odometer_km->setType('info');
		$odometer_km->setTemplate('dashboard','default');
		$odometer_km->setDisplay("showNameOndashboard",1);
		$odometer_km->setSubType('string');
		$odometer_km->setOrder(15);
		$odometer_km->setIsHistorized(1);
		$odometer_km->save();	
		
		// pin
		$pin = $this->getCmd(null, 'pin');
		if (!is_object($pin)) {
			$pin = new m365Cmd();
			$pin->setName(__('Pin', __FILE__));
		}
		$pin->setLogicalId('pin');
		$pin->setEqLogic_id($this->getId());
		$pin->setType('info');
		$pin->setTemplate('dashboard','default');
		$pin->setDisplay("showNameOndashboard",1);
		$pin->setSubType('string');
		$pin->setOrder(16);
		$pin->save();	

		// serial
		$serial = $this->getCmd(null, 'serial');
		if (!is_object($serial)) {
			$serial = new m365Cmd();
			$serial->setName(__('N° Série', __FILE__));
		}
		$serial->setLogicalId('serial');
		$serial->setEqLogic_id($this->getId());
		$serial->setType('info');
		$serial->setTemplate('dashboard','default');
		$serial->setDisplay("showNameOndashboard",1);
		$serial->setSubType('string');
		$serial->setOrder(17);
		$serial->save();	

		// speed_average_kmh
		$speed_average_kmh = $this->getCmd(null, 'speed_average_kmh');
		if (!is_object($speed_average_kmh)) {
			$speed_average_kmh = new m365Cmd();
			$speed_average_kmh->setName(__('Vitesse moyenne', __FILE__));
		}
		$speed_average_kmh->setLogicalId('speed_average_kmh');
		$speed_average_kmh->setEqLogic_id($this->getId());
		$speed_average_kmh->setType('info');
		$speed_average_kmh->setTemplate('dashboard','default');
		$speed_average_kmh->setDisplay("showNameOndashboard",1);
		$speed_average_kmh->setSubType('string');
		$speed_average_kmh->setOrder(18);
		$speed_average_kmh->save();	

		// speed_kmh
		$speed_kmh = $this->getCmd(null, 'speed_kmh');
		if (!is_object($speed_kmh)) {
			$speed_kmh = new m365Cmd();
			$speed_kmh->setName(__('Vitesse', __FILE__));
		}
		$speed_kmh->setLogicalId('speed_kmh');
		$speed_kmh->setEqLogic_id($this->getId());
		$speed_kmh->setType('info');
		$speed_kmh->setTemplate('dashboard','default');
		$speed_kmh->setDisplay("showNameOndashboard",1);
		$speed_kmh->setSubType('string');
		$speed_kmh->setOrder(19);
		$speed_kmh->save();	

		// trip_distance_m
		$trip_distance_m = $this->getCmd(null, 'trip_distance_m');
		if (!is_object($trip_distance_m)) {
			$trip_distance_m = new m365Cmd();
			$trip_distance_m->setName(__('Distance session', __FILE__));
		}
		$trip_distance_m->setLogicalId('trip_distance_m');
		$trip_distance_m->setEqLogic_id($this->getId());
		$trip_distance_m->setType('info');
		$trip_distance_m->setTemplate('dashboard','default');
		$trip_distance_m->setDisplay("showNameOndashboard",1);
		$trip_distance_m->setSubType('string');
		$trip_distance_m->setOrder(20);
		$trip_distance_m->save();	

		// uptime_s
		$uptime_s = $this->getCmd(null, 'uptime_s');
		if (!is_object($uptime_s)) {
			$uptime_s = new m365Cmd();
			$uptime_s->setName(__('Temps session', __FILE__));
		}
		$uptime_s->setLogicalId('uptime_s');
		$uptime_s->setEqLogic_id($this->getId());
		$uptime_s->setType('info');
		$uptime_s->setTemplate('dashboard','default');
		$uptime_s->setDisplay("showNameOndashboard",1);
		$uptime_s->setSubType('string');
		$uptime_s->setOrder(21);
		$uptime_s->save();	

		// version
		$version = $this->getCmd(null, 'version');
		if (!is_object($version)) {
			$version = new m365Cmd();
			$version->setName(__('Version', __FILE__));
		}
		$version->setLogicalId('version');
		$version->setEqLogic_id($this->getId());
		$version->setType('info');
		$version->setTemplate('dashboard','default');
		$version->setDisplay("showNameOndashboard",1);
		$version->setSubType('string');
		$version->setOrder(22);
		$version->save();	

		// update_time
		$update_time = $this->getCmd(null, 'update_time');
		if (!is_object($update_time)) {
			$update_time = new m365Cmd();
			$update_time->setName(__('Date màj', __FILE__));
		}
		$update_time->setLogicalId('update_time');
		$update_time->setEqLogic_id($this->getId());
		$update_time->setType('info');
		$update_time->setTemplate('dashboard','default');
		$update_time->setDisplay("showNameOndashboard",1);
		$update_time->setSubType('string');
		$update_time->setOrder(23);
		$update_time->save();	

		if ($pres->execCmd() == ''){
			$pres->event('inconnue');
		}
		log::add('m365','info','commande présence créée : *'.$pres->execCmd().'*');

		
		$refresh = $this->getCmd(null, 'refresh');
		if (!is_object($refresh)) {
			$refresh = new m365Cmd();
			$refresh->setName(__('Rafraichir', __FILE__));
		}
		$refresh->setEqLogic_id($this->getId());
		$refresh->setLogicalId('refresh');
		$refresh->setType('action');
		$refresh->setSubType('other');
		$refresh->save(); 
		
		// Cruise On
		$cruiseOn = $this->getCmd(null, 'cruiseOn');
		if (!is_object($cruiseOn)) {
			$cruiseOn = new m365Cmd();
			$cruiseOn->setName(__('Cruise On', __FILE__));
		}
		$cruiseOn->setEqLogic_id($this->getId());
		$cruiseOn->setLogicalId('cruiseOn');
		$cruiseOn->setType('action');
		$cruiseOn->setSubType('other');
		$cruiseOn->save(); 
		
		// Cruise Off
		$cruiseOff = $this->getCmd(null, 'cruiseOff');
		if (!is_object($cruiseOff)) {
			$cruiseOff = new m365Cmd();
			$cruiseOff->setName(__('Cruise Off', __FILE__));
		}
		$cruiseOff->setEqLogic_id($this->getId());
		$cruiseOff->setLogicalId('cruiseOff');
		$cruiseOff->setType('action');
		$cruiseOff->setSubType('other');
		$cruiseOff->save(); 

		// Lock On
		$lockOn = $this->getCmd(null, 'lockOn');
		if (!is_object($lockOn)) {
			$lockOn = new m365Cmd();
			$lockOn->setName(__('Lock On', __FILE__));
		}
		$lockOn->setEqLogic_id($this->getId());
		$lockOn->setLogicalId('lockOn');
		$lockOn->setType('action');
		$lockOn->setSubType('other');
		$lockOn->save(); 
		
		// Lock Off
		$lockOff = $this->getCmd(null, 'lockOff');
		if (!is_object($lockOff)) {
			$lockOff = new m365Cmd();
			$lockOff->setName(__('Lock Off', __FILE__));
		}
		$lockOff->setEqLogic_id($this->getId());
		$lockOff->setLogicalId('lockOff');
		$lockOff->setType('action');
		$lockOff->setSubType('other');
		$lockOff->save(); 
		
		// Lumière On
		$lightOn = $this->getCmd(null, 'lightOn');
		if (!is_object($lightOn)) {
			$lightOn = new m365Cmd();
			$lightOn->setName(__('Lumière On', __FILE__));
		}
		$lightOn->setEqLogic_id($this->getId());
		$lightOn->setLogicalId('lightOn');
		$lightOn->setType('action');
		$lightOn->setSubType('other');
		$lightOn->save(); 
		
		// Lumière Off
		$lightOff = $this->getCmd(null, 'lightOff');
		if (!is_object($lightOff)) {
			$lightOff = new m365Cmd();
			$lightOff->setName(__('Lumière Off', __FILE__));
		}
		$lightOff->setEqLogic_id($this->getId());
		$lightOff->setLogicalId('lightOff');
		$lightOff->setType('action');
		$lightOff->setSubType('other');
		$lightOff->save(); 
				
		if ($this->getIsEnable() == 1) {
			log::add('m365','debug','équipement activé');
			$listener = listener::byClassAndFunction('m365', 'bleachange', array('m365_id' => intval($this->getId())));
			if (!is_object($listener)) {
				$listener = new listener();
			}
			$listener->setClass('m365');
			$listener->setFunction('bleachange');
			$listener->setOption(array('m365_id' => intval($this->getId())));
			$listener->emptyEvent();
			$bleacontrol = $this->getConfiguration('bleactrl');
			if ($bleacontrol == '') {
				log::add('m365','info','équipement blea vide');
				return;
			}
			$eq = eqLogic::byId(str_replace('#', '', $bleacontrol));
			if (!is_object($eq)) {
				throw new Exception(__('Equipement blea ' . $bleacontrol . ' inconnu', __FILE__));
			}
			else {
				log::add('m365','debug','Equipement blea '.$bleacontrol. ' trouvé');
			}
			//$listener->addEvent($bleacontrol);
			$cmd = $eq->getCmd(null, 'Present');
        	$cmdId = $cmd->getId($eq);
			$listener->addEvent($cmdId);
			$listener->save();
			$p = $cmd->execCmd();
			if ($p == 0) {
				$pres->event('Absente');
			}	
			else if ($p == 1) {
				$pres->event('Présente');
			}	
			sleep(0.5);
		} else {
			$listener = listener::byClassAndFunction('m365', 'bleachange', array('m365_id' => intval($this->getId())));
			if (is_object($listener)) {
				$listener->remove();
			}
		}
		
		$pres = $this->getCmd(null, 'presence')->execCmd();
		if ($pres == 'Présente')  {
			$this->updateStatus();
		}
		
    }

    public function preUpdate() {
		
    }

    public function postUpdate() {
//		$cmd = $this->getCmd(null, 'refresh'); // On recherche la commande refresh de l’équipement
//		if (is_object($cmd)) { //elle existe et on lance la commande
//			 $cmd->execCmd();
//		}
		self::cronHourly($this->getId());
    }


    public function preRemove() {
		log::add('m365','info','suppression du fichier json de l\'équipement ' . $this->getId());   
		// suppression du listener associé
		$listener = listener::byClassAndFunction('m365', 'bleachange', array('m365_id' => intval($this->getId())));
		if (is_object($listener)) {
			$listener->remove();
		}
		// suppression du fichier de config json de l'équipement supprimé (/data/m365_idEq.json) 
		$resourcePath = realpath(dirname(__FILE__) . '/../../data');
		$configFile = $resourcePath . '/m365_' . $this->getId() . '.json';
		log::add('m365','info','suppression du fichier ' . $configFile . ' de l\'équipement ' . $this->getId());   
		if (file_exists($configFile)) {
			shell_exec('sudo rm ' . $configFile . ' >> ' . log::getPathToLog('m365') . ' 2>&1');
			$cmd = system::getCmdSudo() . 'rm -f '.dirname(__FILE__) . $configFile;
			exec($cmd);
		}
    }

    public function postRemove() {
	}

    /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
      public function toHtml($_version = 'dashboard') {

      }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    }
     */

    /*     * **********************Getteur Setteur*************************** */
}

class m365Cmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

    public function execute($_options = array()) {
		$eqlogic = $this->getEqLogic();
				
		$action = $this->getLogicalId();
		log::add('m365', 'debug', 'appel de l\'action ' . $action);

		if ($eqlogic->getIsEnable() == 1) {
			// équipement actif
			$cmd = $eqlogic->getCmd(null, 'presence');
			$presence = $cmd->execCmd();	
			if ($presence == 'Présente') {
				// trotinette connectée
				log::add('m365', 'debug', 'trotinette connectée');
				switch ($action) { 			
					case 'refresh': 
						log::add('m365', 'debug', 'refresh en cours');
						$eqlogic->updateStatus();
						break;
					case 'cruiseOn' :
					case 'cruiseOff' :
					case 'lockOn' :
					case 'lockOff' :
					case 'lightOn' :
					case 'lightOff' :
						log::add('m365', 'debug', 'action ' . $action . ' en cours');
						$macAdress = $eqlogic->getMacAdress();

						$data_path = dirname(__FILE__) . '/../../resources';
						$shFilename = $data_path . '/'. 'm365actions.sh';
						$autoReconnect = $this->getConfiguration('autoReconnect','0');
						log::add('m365', 'info', 'lancement de l\'action ' . $action . ' avec l\'adresse mac ' . $macAdress . ', autoReconnect=' . $autoReconnect);

						$cmd = system::getCmdSudo() . ' /bin/bash '.$shFilename.' '.$macAdress . ' ' . $action . ' ' . $autoReconnect . ' >> ' . log::getPathToLog('m365') . ' 2>&1';
				
						log::add('m365', 'info', $cmd);
						shell_exec($cmd);

						// refresh de l'affichage pour prise en compte de l'action
						m365::cronHourly($eqlogic->getId());
						break;
				}
			}
		}
    }
    /*     * **********************Getteur Setteur*************************** */
}