<?php
/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class Neuronal extends eqLogic {
	public static function dependancy_info() {
		$return = array();
		$return['log'] = 'Neuronal_update';
		$return['progress_file'] = '/tmp/compilation_Neuronal_in_progress';
		$return['state'] = 'nok';
		if (exec('grep -q "extension=fann.so" /etc/php5/apache2/php.ini') ==1)
			$return['state'] = 'ok';
		if (exec('dpkg -s libfann-dev | grep -c "Status: install"') ==1)
			$return['state'] = 'ok';
		return $return;
	}
	public static function dependancy_install() {
		if (file_exists('/tmp/compilation_Neuronal_in_progress')) {
			return;
		}
		log::remove('Neuronal_update');
		$cmd = 'sudo /bin/bash ' . dirname(__FILE__) . '/../../ressources/install.sh';
		$cmd .= ' >> ' . log::getPathToLog('Neuronal_update') . ' 2>&1 &';
		exec($cmd);
	}
	public static function deamon_info() {
		$return = array();
		$return['log'] = 'Neuronal';
		$return['launchable'] = 'ok';
		$return['state'] = 'nok';
		foreach(eqLogic::byType('Neuronal') as $Neuronal){
			$listener = listener::byClassAndFunction('Neuronal', 'ListenerEvent', array('eqLogic_id' => intval($Neuronal->getId())));
			if (!is_object($listener))
				return $return;
		}
		$return['state'] = 'ok';
		return $return;
	}
	public static function deamon_start($_debug = false) {
		log::remove('Neuronal');
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') 
			return;
		if ($deamon_info['state'] == 'ok') 
			return;
		foreach(eqLogic::byType('Neuronal') as $Neuronal)
			$Neuronal->save();
	}
	public static function deamon_stop() {
		foreach(eqLogic::byType('Neuronal') as $Neuronal){
			$listener = listener::byClassAndFunction('Neuronal', 'ListenerEvent', array('eqLogic_id' => intval($Neuronal->getId())));
			if (is_object($listener))
				$listener->remove();
		}
	}
	public static function ListenerEvent($_options) {
		log::add('Neuronal', 'debug', 'Objet mis à jour => ' . json_encode($_options));
		$eqLogic=eqLogic::byId($_options['eqLogic_id']);
		if (is_object($eqLogic)) {
	      		log::add('Neuronal','debug','Evenement sur une entree de Neurone');
			foreach($eqLogic->getConfiguration('sotries') as $Cmd){
				if($_options['event_id'] == str_replace('#', '', $Cmd['cmd'])){
					$eqLogic->CreateApprentissageTable();
				}
			}
			foreach($eqLogic->getConfiguration('entrees') as $Cmd){
				if($_options['event_id'] == str_replace('#', '', $Cmd['cmd'])){
					$eqLogic->ExecNeurone();
				}
			}
		}
	}
	public static function AddCommande($eqLogic,$Name,$_logicalId) {
		$Commande = $eqLogic->getCmd(null,$_logicalId);
		if (!is_object($Commande))
		{
			$Commande = new NeuronalCmd();
			$Commande->setId(null);
			$Commande->setName($Name);
			$Commande->setLogicalId($_logicalId);
			$Commande->setEqLogic_id($eqLogic->getId());
			$Commande->setType('info');
			$Commande->setSubType('other');
		}
		$Commande->save();
	}	
	public function postSave() {
	      		$this->createListener();
		}
	public function preRemove() {
		$listener = listener::byClassAndFunction('Neuronal', 'ListenerEvent', array('eqLogic_id' => intval($this->getId())));
		if (is_object($listener)) 
			$listener->remove();
	}
	public function createListener(){
		$listener = listener::byClassAndFunction('Neuronal', 'ListenerEvent', array('eqLogic_id' => intval($this->getId())));
		if (!is_object($listener)) {
			log::add('Neuronal','debug','Creation d\'un écouteur d\'evenement :'.$this->getHumanName());
			$listener = new listener();
			$listener->setClass('Neuronal');
			$listener->setFunction('ListenerEvent');
			$listener->setOption(array('eqLogic_id' => intval($this->getId())));
		}
		$listener->emptyEvent();
		foreach ($this->getConfiguration('entrees') as $cmdNeurone) {
			$cmd=cmd::byId(str_replace('#','',$cmdNeurone['cmd']));
			if(is_object($cmd)){
				$listener->addEvent($cmd->getId());
				log::add('Neuronal','debug','Ajout de '.$cmd->getHumanName().' de l\'écouteur d\'evenement :'.$this->getHumanName());
			}
		}
		foreach ($this->getConfiguration('sotries') as $cmdNeurone) {
			$cmd=cmd::byId(str_replace('#','',$cmdNeurone['cmd']));
			if(is_object($cmd)){
				$listener->addEvent($cmd->getId());
				log::add('Neuronal','debug','Ajout de '.$cmd->getHumanName().' de l\'écouteur d\'evenement :'.$this->getHumanName());
			}
		}
		$listener->save();
		log::add('Neuronal','debug','Lancement de l\'écouteur d\'evenement :'.$this->getHumanName());
	}
	public function ExecNeurone() {	
      		log::add('Neuronal','debug','Execution du resau de neurone');
		$train_file = (dirname(__FILE__) . "/../../ressources/".$this->getHumanName().".net");
		if (!is_file($train_file)) 
			return;
		$ann = fann_create_from_file($train_file);
		if ($ann) {
			$Entree=array();
			foreach ($this->getConfiguration('entrees') as $cmdNeurone) {
				$cmd = cmd::byId(str_replace('#', '', $Commande['cmd']));
				if(is_object($cmd)){
					log::add('Neuronal','debug','Ajout d\'une valeur a la table de calibration pour le neurone :'.$this->getHumanName().$cmd->getHumanName());
					$Entree[]=$cmd->execCmd();
				}
			}
			 if ($output=fann_run($ann, $Entree) === FALSE )
		 		return;
			log::add('Neuronal','debug','Resultat de l\'execution du neurone :'.json_encode($output));;
		    	fann_destroy($ann);
		}
	}
	public function CreateApprentissageTable() {
		$NbEntree=count($this->getConfiguration('entrees'));
		$NbSorite=count($this->getConfiguration('sotries'));
		$num_layers = 3;
		$num_neurons_hidden = 70;
		$desired_error = 0.001;
		$max_epochs = 5000000;
		$epochs_between_reports = 1000;
		$ann = fann_create_standard($num_layers, $NbEntree, $num_neurons_hidden, $NbSorite);
		if ($ann) {
			fann_set_activation_function_hidden($ann, FANN_SIGMOID_SYMMETRIC);
			fann_set_activation_function_output($ann, FANN_SIGMOID_SYMMETRIC);
			$input=array();
			foreach ($this->getConfiguration('entrees') as $cmdNeurone) {
				$cmd = cmd::byId(str_replace('#', '', $cmdNeurone['cmd']));
				if(is_object($cmd)){
					log::add('Neuronal','debug','Ajout d\'une valeur a la table de calibration pour le neurone :'.$this->getHumanName().$cmd->getHumanName());
					$input[]=$cmd->execCmd();
				}
			}
			if(fann_train ($ann , $input , $desired_output ))
				fann_save($ann,dirname(__FILE__) . "/../../ressources/".$this->getHumanName().".net");
				
			fann_destroy($ann);
		}
		log::add('Neuronal','debug','Mise a jours de la table de calibration pour le neurone :'.$this->getHumanName());
	}
}
class NeuronalCmd extends cmd {
	public function execute($_options = array())	{
	}
}
