<?php

/**
 * ClasseViva API PHP
 * 
 * Code by 0fabris
 * 
 * Made for TelegramBot that shows weekly events
 * 
 * 03/2020 ft. COVID19 aka CoronaVirus
 * 
 * (my first use of classes in php :-P)
 * 
 * inspired by Classeviva-Official-Endpoints
 * 
 * This code wasn't tested, it may have some errors
 */

//Namespace
namespace _fabris;

//Constants
const API_URL = "https://web.spaggiari.eu/rest/v1";

//Class Definition
class ClasseViva{

	//Attributes
	private $loginfos;

	private static $headers = array(
                'Content-Type: application/json',
                'Z-Dev-Apikey: +zorro+',
                'User-Agent: zorro/1.0',
            );

	/**
	 * Constructor
	 * 
	 * Class Usage:
	 * 		$b = ClasseViva($username,$password);
	 * 		echo $b->getDidattica();
	 * 		... etc. etc.
	 */
	 public function __construct(String $u, String $p){
		$this->loginfos = $this->Login($u,$p);
	}
	
	/**
	 * Method to request something to CV Api (POST and GET)
	 */
	private function getApiResp($url,$postargs=null,$hds=null){
		if(is_null($hds))
			$hds = ClasseViva::$headers;

		$c = curl_init(API_URL.$url);
		curl_setopt_array($c, [
			CURLOPT_POST 			=> !is_null($postargs),
			CURLOPT_FORBID_REUSE   	=> true,
			CURLOPT_RETURNTRANSFER 	=> true,
			CURLOPT_SSL_VERIFYPEER 	=> false,
			CURLOPT_HTTPHEADER 		=> $hds, 
		]);
		if(!is_null($postargs)){
			curl_setopt_array($c,[
				CURLOPT_POST 		=> true,
				CURLOPT_POSTFIELDS	=> $postargs,
			]);
		}
		return curl_exec($c);
	}


	/**
	 * Login method, POST to /auth/login with parameters
	 */
	public function Login($u,$p){
		return json_decode(
			$this->getApiResp(
				"/auth/login",
				$postargs="{\"ident\":null,\"pass\":\"$p\",\"uid\":\"$u\"}"
			),
			1
		);
	}

	/**
	 * Gets the numeric part of Student ID
	 * ex. S12345678X => 12345678
	 */
	private function getIdent(){
		return substr($this->loginfos["ident"],1,-1);
	}

	/**
	 * Get Agenda from Schoolyear Start to End if no parameters.
	 * Parameter iniz and fine are Start and Stop Dates (yyyymmgg format)
	 */
	public function getAgenda($iniz="20190925",$fine="20200610"){
		return $this->getApiResp(
					$url="/students/{$this->getIdent()}/agenda/all/$iniz/$fine",
					$postargs = null,
					$hds=array_merge(
						ClasseViva::$headers,
						['Z-Auth-Token: '.$this->loginfos["token"]]
					)
				);
	}

	/**
	 * Get Didattica Section
	 */
	public function getDidattica(){
		return $this->getApiResp(
					$url = "/students/{$this->getIdent()}/didactics",
					$postargs =  null,
					$hds=array_merge(
						ClasseViva::$headers,
						['Z-Auth-Token: '.$this->loginfos["token"]]
					)
				);
	}
	
	/**
	 * Get Didattica Item by ID
	 */
	public function getDidatticaItem($item){
		return $this->getApiResp(
					$url = "/students/{$this->getIdent()}/didactics/item/$item",
					$postargs =  null,
					$hds=array_merge(
						ClasseViva::$headers,
						['Z-Auth-Token: '.$this->loginfos["token"]]
					)
				);
	}

	/**
	 * Get Assenze
	 * parameters in and fine => determine interval of absences
	 */
	public function getAssenze($in=null,$fine=null){
		return $this->getApiResp(
			$url = "/students/{$this->getIdent()}/absences/details".(is_null($in)?"":"/".$in).((!is_null($in) && is_null($fine))?"":"/".$fine),
			$postargs = null,
			$hds=array_merge(
				ClasseViva::$headers,
				['Z-Auth-Token: '.$this->loginfos["token"]]
			)
		);
	}

	/**
	 * Get Noticeboard
	 */
	public function getNoticeBoard(){
		return $this->getApiResp(
			$url = "/students/{$this->getIdent()}/noticeboard",
			$postargs = null,
			$hds=array_merge(
				ClasseViva::$headers,
				['Z-Auth-Token: '.$this->loginfos["token"]]
			)
		);
	}

	/**
	 * Get SchoolBooks
	 */
	public function getSchoolBooks(){
		return $this->getApiResp(
			$url = "/students/{$this->getIdent()}/schoolbooks",
			$postargs = null,
			$hds=array_merge(
				ClasseViva::$headers,
				['Z-Auth-Token: '.$this->loginfos["token"]]
			)
		);
	}

	/**
	 * Get Calendar, don't know what this does, there is agenda??
	 */
	public function getCalendar(){
		return $this->getApiResp(
			$url = "/students/{$this->getIdent()}/calendar/all",
			$postargs = null,
			$hds=array_merge(
				ClasseViva::$headers,
				['Z-Auth-Token: '.$this->loginfos["token"]]
			)
		);
	}

	/**
	 * Get Cards
	 */
	public function getCards(){
		return $this->getApiResp(
			$url = "/students/{$this->getIdent()}/cards",
			$postargs = null,
			$hds=array_merge(
				ClasseViva::$headers,
				['Z-Auth-Token: '.$this->loginfos["token"]]
			)
		);
	}

	/**
	 * Get Grades - Voti
	 */
	public function getGrades(){
		return $this->getApiResp(
			$url = "/students/{$this->getIdent()}/grades",
			$postargs = null,
			$hds=array_merge(
				ClasseViva::$headers,
				['Z-Auth-Token: '.$this->loginfos["token"]]
			)
		);
	}

	/**
	 * Get Periods - Ore
	 */
	public function getPeriods(){
		return $this->getApiResp(
			$url = "/students/{$this->getIdent()}/periods",
			$postargs = null,
			$hds=array_merge(
				ClasseViva::$headers,
				['Z-Auth-Token: '.$this->loginfos["token"]]
			)
		);
	}

	/**
	 * Get Subjects - Materie
	 */
	public function getSubjects(){
		return $this->getApiResp(
			$url = "/students/{$this->getIdent()}/subjects",
			$postargs = null,
			$hds=array_merge(
				ClasseViva::$headers,
				['Z-Auth-Token: '.$this->loginfos["token"]]
			)
		);
	}

	/**
	 * Get Documents - Pagella
	 */
	public function getDocuments(){
		return $this->getApiResp(
			$url = "/students/{$this->getIdent()}/documents",
			$postargs = "{}",
			$hds=array_merge(
				ClasseViva::$headers,
				['Z-Auth-Token: '.$this->loginfos["token"]]
			)
		);
	}
}

