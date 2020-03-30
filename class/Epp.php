<?php
namespace NicBr\Epp;

/**
 * EPP Class
 *
 * This is the main class for EPP functions based on brazilian Registro.br registrar.
 *
 * PHP version 5.4
 *
 * @author Diogo Tozzi <diogo@diogotozzi.com>
 * @copyright 2012 - Diogo Tozzi
 * @link http://github.com/diogotozzi/Epp
 * @version 1.0
 */
class Epp extends EppBase implements iEpp
{

    /**
     * Returns information from a contact
     * This function returns all informations from a contact.
     *
     * @param string $client_id
     *            Client ID to seek.
     * @return array Contact's information
     * @access public
     */
    public function contact_info($client_id = null)
    {
        $xml = file_get_contents(__DIR__ . '/templates/contact_info.xml');
        
        $auth_info = "<contact:authInfo>
						<contact:pw>{$this->getPassword()}</contact:pw>
						</contact:authInfo>";
        
        $cltrid = '<clTRID>' . $this->generate_id() . '</clTRID>';
        
        $xml = str_replace('$(id)$', $client_id, $xml);
        $xml = str_replace('$(auth_info)$', $auth_info, $xml);
        $xml = str_replace('$(clTRID)$', $cltrid, $xml);
        
        $xml = $this->wrap($xml);
        
        $this->send_command($xml);
        
        $response = $this->xml2array($this->unwrap());
        
        if ($response['epp']['response']['result_attr']['code'] != '1000') {
            $reason = ($response['epp']['response']['result']['extValue']['reason'] ? ' - ' . $response['epp']['response']['result']['extValue']['reason'] : '');
            throw new \Exception($response['epp']['response']['result']['msg'] . $reason, $response['epp']['response']['result_attr']['code']);
        }
        
        $infData = $response['epp']['response']['resData']['contact:infData'];
        $data = array(  
            'client_id' => $infData['contact:id'],
            'client_roid' => $infData['contact:roid'],
            'client_name' => $infData['contact:postalInfo']['contact:name'],
            'client_city' => (isset($infData['contact:postalInfo']['contact:addr']['contact:city']) ? $infData['contact:postalInfo']['contact:addr']['contact:city'] : ''),
            'client_state' => (isset($infData['contact:postalInfo']['contact:addr']['contact:sp']) ? $infData['contact:postalInfo']['contact:addr']['contact:sp'] : ''),
            'client_zipcode' => (isset($infData['contact:postalInfo']['contact:addr']['contact:pc']) ? $infData['contact:postalInfo']['contact:addr']['contact:pc'] : ''),
            'client_country' => (isset($infData['contact:postalInfo']['contact:addr']['contact:cc']) ? $infData['contact:postalInfo']['contact:addr']['contact:cc'] : ''),
            'client_phone' => $infData['contact:voice'],
            'client_email' => $infData['contact:email'],
            'client_create' => $infData['contact:crDate'],
            'client_update' => (isset($infData['contact:upDate'])) ? $infData['contact:upDate'] : null
        );
        foreach ($infData['contact:postalInfo']['contact:addr']['contact:street'] as $key => $street) {
            $client_address = 'client_address_' . ($key + 1);
            $data[$client_address] = $street;
        }
        return $data;
    }

    /**
     * Creates a new contact
     *
     * This function creates a new contact.
     *
     * @param string $client_name
     *            Full name.
     * @param string $client_street_1
     *            Address.
     * @param string $client_street_2
     *            Address Number
     * @param string $client_street_3
     *            Address Complement
     * @param string $client_city
     *            City. Eg: 'São Paulo'.
     * @param string $client_state
     *            State. Eg: 'SP'.
     * @param string $client_zipcode
     *            Zipcode. Eg: '00000-000'.
     * @param string $client_country
     *            country. Default is 'BR'.
     * @param string $client_phone
     *            Phone. Required the country code Eg: '+55.1100000000'.
     * @param string $client_email
     *            E-mail. Eg: 'test@test.com'
     *            
     * @return array Returns the information of the new contact.
     *        
     * @access public
     */
    public function contact_create($client_name = null, $client_street_1 = null, $client_street_2 = null, $client_street_3 = null, $client_city = null, $client_state = null, $client_zipcode = null, $client_country = 'BR', $client_phone = null, $client_email = null)
    {
        $xml = file_get_contents(__DIR__ . '/templates/contact_create.xml');
        
        $postal_info = "<contact:postalInfo type=\"loc\">
							<contact:name>{$client_name}</contact:name>
							<contact:org></contact:org>
							<contact:addr>
								<contact:street>{$client_street_1}</contact:street>
							<contact:street>{$client_street_2}</contact:street>" . ($client_street_3 ? "<contact:street>{$client_street_3}</contact:street>" : null) . "<contact:street>{$client_street_3}</contact:street>
								<contact:city>{$client_city}</contact:city>
								<contact:sp>{$client_state}</contact:sp>
								<contact:pc>{$client_zipcode}</contact:pc>
								<contact:cc>{$client_country}</contact:cc>
							</contact:addr>
						</contact:postalInfo>";
        
        $voice = "<contact:voice>{$client_phone}</contact:voice>";
        
        $auth_info = "<contact:authInfo>
						<contact:pw>{$this->getPassword()}</contact:pw>
						</contact:authInfo>";
        
        $cltrid = '<clTRID>' . $this->generate_id() . '</clTRID>';
        
        $xml = str_replace('<contact:id>$(id)$</contact:id>', '', $xml);
        $xml = str_replace('$(postal_info)$', $postal_info, $xml);
        $xml = str_replace('$(voice)$', $voice, $xml);
        $xml = str_replace('$(fax)$', '', $xml);
        $xml = str_replace('$(email)$', $client_email, $xml);
        $xml = str_replace('$(auth_info)$', $auth_info, $xml);
        $xml = str_replace('$(disclose)$', '', $xml);
        $xml = str_replace('$(clTRID)$', $cltrid, $xml);
        
        $xml = $this->wrap($xml);
        
        $this->send_command($xml);
        
        $response = $this->xml2array($this->unwrap());
        
        if ($response['epp']['response']['result_attr']['code'] != '1000') {
            $reason = ($response['epp']['response']['result']['extValue']['reason'] ? ' - ' . $response['epp']['response']['result']['extValue']['reason'] : '');
            throw new \Exception($response['epp']['response']['result']['msg'] . $reason, $response['epp']['response']['result_attr']['code']);
        }
        
        $data = array(
            'client_id' => $response['epp']['response']['resData']['contact:creData']['contact:id'],
            'client_creation' => $response['epp']['response']['resData']['contact:creData']['contact:crDate'],
            'client_cltrid' => $response['epp']['response']['trID']['clTRID']
        );
        
        return $data;
    }

    /**
     * Updates a contact
     *
     * This function updates all contact's information fields
     *
     * @param string $client_name
     *            Full name.
     * @param string $client_street_1
     *            Address.
     * @param string $client_street_2
     *            Address Number
     * @param string $client_street_3
     *            Address Complement
     * @param string $client_city
     *            City. Eg: 'São Paulo'.
     * @param string $client_state
     *            State. Eg: 'SP'.
     * @param string $client_zipcode
     *            Zipcode. Eg: '00000-000'.
     * @param string $client_country
     *            country. Default is 'BR'.
     * @param string $client_phone
     *            Phone. Required the country code Eg: '+55.1100000000'.
     * @param string $client_email
     *            E-mail. Eg: 'test@test.com'
     *            
     * @return array Returns the contact's updated information. array( 'code' => 1010, 'msg' => 'message' )
     *        
     * @access public
     */
    public function contact_update($client_id = null, $client_street_1 = null, $client_street_2 = null, $client_street_3 = null, $client_city = null, $client_state = null, $client_zipcode = null, $client_country = 'BR', $client_phone = null, $client_email = null)
    {
        $xml = file_get_contents(__DIR__ . '/templates/contact_update.xml');
        
        $contact_addr = ($client_street_1 ? "<contact:street>{$client_street_1}</contact:street>" : '')
            .($client_street_2 ? "<contact:street>{$client_street_2}</contact:street>" : '')
            .($client_street_3 ? "<contact:street>{$client_street_3}</contact:street>" : '')
            .($client_city ? "<contact:city>{$client_city}</contact:city>" : '')
            .($client_state ? "<contact:sp>{$client_state}</contact:sp>" : '')
            .($client_zipcode ? "<contact:pc>{$client_zipcode}</contact:pc>" : '')
            .($client_country ? "<contact:cc>{$client_country}</contact:cc>" : '' );
        
        $contact_addr = ($contact_addr ? "<contact:addr>$contact_addr</contact:addr>" : '');
        
        $contact_postal_info = ( $contact_addr ? "<contact:postalInfo type=\"loc\">{$contact_addr}</contact:postalInfo>" : '');
        
        $contact_voice = ($client_phone ? "<contact:voice>{$client_phone}</contact:voice>" : '' );
        $contact_mail = ($client_email ? "<contact:email>{$client_email}</contact:email>" : '' );
        $contact_info = "<contact:authInfo><contact:pw>{$this->getPassword()}</contact:pw></contact:authInfo>";
                
        $chg = "<contact:chg>
                {$contact_postal_info}
                {$contact_voice}
                {$contact_mail}
                {$contact_info}
            </contact:chg>";
        
        $cltrid = '<clTRID>' . $this->generate_id() . '</clTRID>';
        
        $xml = str_replace('$(id)$', $client_id, $xml);
        $xml = str_replace('$(add)$', '', $xml);
        $xml = str_replace('$(rem)$', '', $xml);
        $xml = str_replace('$(chg)$', $chg, $xml);
        $xml = str_replace('$(clTRID)$', $cltrid, $xml);
        
        $xml = $this->wrap($xml);
        
        $this->send_command($xml);
        
        $response = $this->xml2array($this->unwrap());
        
        if ($response['epp']['response']['result_attr']['code'] != '1000' && $response['epp']['response']['result_attr']['code'] != '1001') {
            $reason = ($response['epp']['response']['result']['extValue']['reason'] ? ' - ' . $response['epp']['response']['result']['extValue']['reason'] : '');
            throw new \Exception($response['epp']['response']['result']['msg'] . $reason, $response['epp']['response']['result_attr']['code']);
        }
        
        $data = array(
            'code' => $response['epp']['response']['result_attr']['code'],
            'msg' => $response['epp']['response']['result']['msg'],
            'reason' => $reason = ( isset($response['epp']['response']['result']['extValue']['reason']) ? $response['epp']['response']['result']['extValue']['reason'] : '')
        );
        
        return $data;
    }

    /**
     * Checks if an organization exists.
     * Checks if an organization already exists with CPF or CNPJ.
     *
     * @param string $org_id
     *            Organization's CPF or CNPJ. Eg: '246.838.523-30'.
     * @return array Returns all organization's information
     *        
     * @access public
     */
    public function org_check($org_id = null)
    {
        $xml = file_get_contents(__DIR__ . '/templates/br_org_check.xml');
        
        $organization_list = "<brorg:cd>
								<brorg:id>{$org_id}</brorg:id>
								<brorg:organization>{$org_id}</brorg:organization>
							</brorg:cd>";
        
        $cltrid = '<clTRID>' . $this->generate_id() . '</clTRID>';
        
        $xml = str_replace('<contact:id>$(id_list)$</contact:id>', '', $xml);
        $xml = str_replace('$(organization_list)$', $organization_list, $xml);
        $xml = str_replace('$(clTRID)$', $cltrid, $xml);
        
        $xml = $this->wrap($xml);
        
        $this->send_command($xml);
        
        $response = $this->xml2array($this->unwrap());
        
        if ($response['epp']['response']['result_attr']['code'] != '1000') {
            $reason = ($response['epp']['response']['result']['extValue']['reason'] ? ' - ' . $response['epp']['response']['result']['extValue']['reason'] : '');
            throw new \Exception($response['epp']['response']['result']['msg'] . $reason, $response['epp']['response']['result_attr']['code']);
        }
        
        $contactCd = $response['epp']['response']['resData']['contact:chkData']['contact:cd'];
        $data = array(
            'org_id' => $contactCd['contact:id'],
            'org_available' => $contactCd['contact:id_attr']['avail'],
            'org_reason' => (isset($contactCd['contact:reason'])) ? $contactCd['contact:reason'] : ''
        );
        
        return $data;
    }

    /**
     * Searches for an organization.
     * Searches all information from an Organization
     *
     * @param string $org_id
     *            Organization's CPF or CNPJ. Eg: '246.838.523-30'.
     * @return array Returns all organization's information
     *        
     * @access public
     */
    public function org_info($org_id = null)
    {
        $xml = file_get_contents(__DIR__ . '/templates/br_org_info.xml');
        
        $auth_info = "<contact:authInfo>
						<contact:pw>{$this->getPassword()}</contact:pw>
						</contact:authInfo>";
        
        $cltrid = '<clTRID>' . $this->generate_id() . '</clTRID>';
        
        $xml = str_replace('<contact:id>$(id)$</contact:id>', '', $xml);
        $xml = str_replace('$(organization)$', $org_id, $xml);
        $xml = str_replace('$(auth_info)$', $auth_info, $xml);
        $xml = str_replace('$(clTRID)$', $cltrid, $xml);
        
        $xml = $this->wrap($xml);
        
        $this->send_command($xml);
        
        $response = $this->xml2array($this->unwrap());
        
        if ($response['epp']['response']['result_attr']['code'] != '1000') {
            $reason = (isset($response['epp']['response']['result']['extValue']['reason']) ? ' - ' . $response['epp']['response']['result']['extValue']['reason'] : '');
            throw new \Exception($response['epp']['response']['result']['msg'] . $reason, $response['epp']['response']['result_attr']['code']);
        }
        
        $infData = $response['epp']['response']['resData']['contact:infData'];
        $extension_infData = $response['epp']['response']['extension']['brorg:infData'];
        $data = array(
            'org_id' => $infData['contact:id'],
            'org_roid' => $infData['contact:roid'],
            'org_name' => $infData['contact:postalInfo']['contact:name'],
            'org_status' => array(),
            'org_city' => isset($infData['contact:postalInfo']['contact:addr']['contact:city']) ? $infData['contact:postalInfo']['contact:addr']['contact:city'] : '',
            'org_state' => isset($infData['contact:postalInfo']['contact:addr']['contact:sp']) ? $infData['contact:postalInfo']['contact:addr']['contact:sp'] : '',
            'org_zipcode' => isset($infData['contact:postalInfo']['contact:addr']['contact:pc']) ? $infData['contact:postalInfo']['contact:addr']['contact:pc'] : '',
            'org_country' => isset($infData['contact:postalInfo']['contact:addr']['contact:cc']) ? $infData['contact:postalInfo']['contact:addr']['contact:cc'] : '',
            'org_phone' => $infData['contact:voice'],
            'org_email' => $infData['contact:email'],
            'org_client_id' => $infData['contact:clID'],
            'org_create_id' => $infData['contact:crID'],
            'org_create' => $infData['contact:crDate'],
            'org_update' => (isset($infData['contact:upDate'])) ? $infData['contact:upDate'] : '',
            'org_contact' => array(),
            'org_suspended' => (isset($extension_infData['brorg:suspended']) ? $extension_infData['brorg:suspended'] : '')
        );
        // Address
        foreach ($infData['contact:postalInfo']['contact:addr']['contact:street'] as $key => $street) {
            $org_address = 'org_address_' . ($key + 1);
            $data[$org_address] = $street;
        }
        // Org Contacts (for NICBR the 'admin' contact always exists, but can exists more
        if(isset($extension_infData['domain:contact'][0])){
            foreach ($extension_infData['domain:contact'] as $key => $contact) {
                if (!is_array($contact)) {
                    $type = $resData_InfData['domain:contact']["{$key}_attr"]['type'];
                    $data['org_contact'][$type] = $contact;
                }
            }
        }else{
            $type = (isset($extension_infData['brorg:contact_attr']['type']) ? $extension_infData['brorg:contact_attr']['type'] : '');
            $contact = (isset($extension_infData['brorg:contact']) ? $extension_infData['brorg:contact'] : '');
            $data['org_contact'][$type] = $contact;
        }
        //EPP contact status
        foreach ($infData['contact:status'] as $key => $status) {
            if (isset($status['s'])) {
                $data['org_status'][] = $status['s'];
            }
        }
        //List of domains for this org
        if (isset($extension_infData['brorg:domainName'])) {
            $data['org_domain_name'] = array();
            foreach ($extension_infData['brorg:domainName'] as $key => $domain_name) {
                $data['org_domain_name'][] = $domain_name;
            }
        }
        //IP IP range
        if (isset($extension_infData['brorg:ipRange'])) {
            $data['org_ip_range'] = array(
                'version' => (isset($extension_infData['brorg:ipRange_attr']['version']) ? $extension_infData['brorg:ipRange_attr']['version'] : ''),
                'start_address' => (isset($extension_infData['brorg:startAddress']) ? $extension_infData['brorg:startAddress'] : ''),
                'end_address' => (isset($extension_infData['brorg:endAddress']) ? $extension_infData['brorg:endAddress'] : '')
            );
        }
        return $data;
    }

    /**
     * Creates a new organization
     *
     * Creates a new organization using a contact previously created.
     *
     * @param string $org_id
     *            Organization's CPF or CNPJ. Eg: '246.838.523-30'.
     * @param string $org_name
     *            Name.
     * @param string $org_street_1
     *            Address.
     * @param string $org_street_2
     *            Address Number
     * @param string $org_street_3
     *            Address Complement
     * @param string $org_city
     *            City. Eg: 'São Paulo'.
     * @param string $org_state
     *            State. Eg: 'SP'.
     * @param string $org_zipcode
     *            Zipcode. Eg: '00000-000'.
     * @param string $org_country
     *            Country. Default is 'BR'.
     * @param string $org_phone
     *            Phone. Required the country code. Eg: '+55.1100000000'.
     * @param string $org_email
     *            E-mail. Eg: 'test@test.com'.
     * @param string $contact_id
     *            ID from a contact previously created. Eg: 'JOSIL44'.
     * @param string $contact_name
     *            Contact's name.
     *            
     * @return array Returns all organization's information.
     *        
     * @access public
     */
    public function org_create($org_id = null, $org_name = null, $org_street_1 = null, $org_street_2 = null, $org_street_3 = null, $org_city = null, $org_state = null, $org_zipcode = null, $org_country = 'BR', $org_phone = null, $org_email = null, $contact_admin_id = null, $contact_tech_id = null, $contact_billing_id = null, $responsible = null)
    {
        if (! $contact_tech_id) {
            $contact_tech_id = $contact_admin_id;
        }
        if (! $contact_billing_id) {
            $contact_billing_id = $contact_admin_id;
        }
        $xml = file_get_contents(__DIR__ . '/templates/br_org_create.xml');
        
        // Street 3 é opcional
        $postal_info = "<contact:postalInfo type=\"loc\">
							<contact:name>{$org_name}</contact:name>
							<contact:addr>
								<contact:street>{$org_street_1}</contact:street>
							<contact:street>{$org_street_2}</contact:street>" 
							    .($org_street_3 ? "<contact:street>{$org_street_3}</contact:street>" : null) 
							    ."<contact:city>{$org_city}</contact:city>
								<contact:sp>{$org_state}</contact:sp>
								<contact:pc>{$org_zipcode}</contact:pc>
								<contact:cc>{$org_country}</contact:cc>
							</contact:addr>
						</contact:postalInfo>";
        
        $voice = "<contact:voice>{$org_phone}</contact:voice>";
        
        $auth_info = "<contact:authInfo>
						<contact:pw>{$this->getPassword()}</contact:pw>
						</contact:authInfo>";
        
        $brorg_contact_list = "<brorg:contact type=\"admin\">{$contact_admin_id}</brorg:contact>
							<brorg:contact type=\"tech\">{$contact_tech_id}</brorg:contact>
							<brorg:contact type=\"billing\">{$contact_billing_id}</brorg:contact>";
        
        $responsible = "<brorg:responsible>{$responsible}</brorg:responsible>";
        
        $cltrid = '<clTRID>' . $this->generate_id() . '</clTRID>';
        
        $xml = str_replace('$(id)$', $org_id, $xml);
        $xml = str_replace('$(postal_info)$', $postal_info, $xml);
        $xml = str_replace('$(voice)$', $voice, $xml);
        $xml = str_replace('$(fax)$', '', $xml);
        $xml = str_replace('$(email)$', $org_email, $xml);
        $xml = str_replace('$(auth_info)$', $auth_info, $xml);
        $xml = str_replace('$(disclose)$', '', $xml);
        $xml = str_replace('$(organization)$', $org_id, $xml);
        $xml = str_replace('$(brorg_contact_list)$', $brorg_contact_list, $xml);
        $xml = str_replace('$(responsible)$', $responsible, $xml);
        $xml = str_replace('$(clTRID)$', $cltrid, $xml);
        
        $xml = $this->wrap($xml);
        
        $this->send_command($xml);
        
        $response = $this->xml2array($this->unwrap());
        
        if ($response['epp']['response']['result_attr']['code'] != '1000' && $response['epp']['response']['result_attr']['code'] != '1001') {
            $reason = (isset($response['epp']['response']['result']['extValue']['reason']) ? ' - ' . $response['epp']['response']['result']['extValue']['reason'] : '');
            throw new \Exception($response['epp']['response']['result']['msg'] . $reason, $response['epp']['response']['result_attr']['code']);
        }
        
        $data = array(
            'code_id' => $response['epp']['response']['result_attr']['code'],
            'code_message' => $response['epp']['response']['result']['msg'],
            'org_id' => $response['epp']['response']['resData']['contact:creData']['contact:id'],
            'org_creation' => $response['epp']['response']['resData']['contact:creData']['contact:crDate']
        );
        
        return $data;
    }

    /**
     * Updates an organization
     *
     * Updates all organization's information.
     *
     * @param string $org_id
     *            Organization's CPF or CNPJ. Eg: '246.838.523-30'.
     * @param string $org_street_1
     *            Address.
     * @param string $org_street_2
     *            Address Number
     * @param string $org_street_3
     *            Address Complement
     * @param string $org_city
     *            City. Eg: 'São Paulo'.
     * @param string $org_state
     *            State. Eg: 'SP'.
     * @param string $org_zipcode
     *            Zipcode. Eg: '00000-000'.
     * @param string $org_country
     *            Country. Default is 'BR'.
     * @param string $org_phone
     *            Phone. Required the country code. Eg: '+55.1100000000'.
     * @param string $org_id
     *            Organization's CPF or CNPJ. Eg: '246.838.523-30'.
     * @param string $contact_admin_id
     *            Contact's ID previously created. Eg: 'PTER1'.
     * @param string $responsible Name of the responsible
     *            
     * @return array Returns organization's updated information array('code' => 1010, 'msg' => 'message' )
     *        
     * @access public
     */
    public function org_update($org_id, $org_name=null, $org_street_1 = null, $org_street_2 = null, $org_street_3 = null, $org_city = null, $org_state = null, $org_zipcode = null, $org_country = 'BR', $org_phone = null, $contact_admin_id = null, $responsible = null)
    {
        $org_info = $this->org_info($org_id);
        $contact_admin_id_old = $org_info['org_contact']['admin'];
        
        $xml = file_get_contents(__DIR__ . '/templates/br_org_update.xml');
        
        $contact_addr = ($org_street_1 ? "<contact:street>{$org_street_1}</contact:street>" : '')
            .($org_street_2 ? "<contact:street>{$org_street_2}</contact:street>" : '')
            .($org_street_3 ? "<contact:street>{$org_street_3}</contact:street>" : '')
            .($org_city ? "<contact:city>{$org_city}</contact:city>" : '')
            .($org_state ? "<contact:sp>{$org_state}</contact:sp>" : '')
            .($org_zipcode ? "<contact:pc>{$org_zipcode}</contact:pc>" : '')
            .($org_country ? "<contact:cc>{$org_country}</contact:cc>" : '' );
        
        $contact_name = ($org_name ? "<contact:name>{$org_name}</contact:name>":  '' );
        $contact_addr = ($contact_addr ? "<contact:addr>$contact_addr</contact:addr>" : '');
        $contact_postal_info = ($contact_addr || $contact_name ? "<contact:postalInfo type=\"loc\">{$contact_name}{$contact_addr}</contact:postalInfo>" : '');
        $contact_voice = ($org_phone ? "<contact:voice>{$org_phone}</contact:voice>" : '');
        
        $chg = "<contact:chg>
                $contact_postal_info
                $contact_voice
    			<contact:authInfo>
    			     <contact:pw>{$this->getPassword()}</contact:pw>
    			  </contact:authInfo>
    		</contact:chg>";

		/*
		 * Remove the old contact and add the new one
		 */
		$brorg_add = null;
		$brorg_rem = null;
		if($contact_admin_id){
            $brorg_add  = "<brorg:add>";
            $brorg_add .= "<brorg:contact type=\"admin\">$contact_admin_id</brorg:contact>";
            $brorg_add .= "</brorg:add>";
            $brorg_rem  = "<brorg:rem>";
            $brorg_rem .= "<brorg:contact type=\"admin\">$contact_admin_id_old</brorg:contact>";
            $brorg_rem .= "</brorg:rem>";
        }
		$brorg_chg = $responsible ? "<brorg:chg><brorg:responsible>{$responsible}</brorg:responsible></brorg:chg>" : null;
        
        $cltrid = '<clTRID>' . $this->generate_id() . '</clTRID>';
        
        $xml = str_replace('$(id)$', $org_id, $xml);
        $xml = str_replace('$(add)$', '', $xml);
        $xml = str_replace('$(rem)$', '', $xml);
        $xml = str_replace('$(chg)$', $chg, $xml);
        $xml = str_replace('$(organization)$', $org_id, $xml);
        $xml = str_replace('$(brorg_add)$', $brorg_add, $xml);
        $xml = str_replace('$(brorg_rem)$', $brorg_rem, $xml);
        $xml = str_replace('$(brorg_chg)$', $brorg_chg, $xml);
        $xml = str_replace('$(clTRID)$', $cltrid, $xml);
        
        $xml = $this->wrap($xml);
        
        $this->send_command($xml);
        
        $response = $this->xml2array($this->unwrap());
        
        if ($response['epp']['response']['result_attr']['code'] != '1000' && $response['epp']['response']['result_attr']['code'] != '1001') {
            if(isset($response['epp']['response']['result']['extValue'][0])){
                //If ..['extValue'][0]['reason'] exists so there are multiples reasons
                $reason = '';
                foreach ($response['epp']['response']['result']['extValue'] as $key => $contactError ){
                    $reason .= " Error[". ($key+1) . ']: ' . $contactError['reason'] . ': Contact ' . $contactError['value']['brorg:contact'] . " Type '" . $contactError['value']['brorg:contact_attr']['type'] ."'";
                    
                }
            }else{
                $reason = ($response['epp']['response']['result']['extValue']['reason'] ? ' - ' . $response['epp']['response']['result']['extValue']['reason'] : '');
            }
            throw new \Exception($response['epp']['response']['result']['msg'] . " => " .$reason, $response['epp']['response']['result_attr']['code']);
        }
        
        $data = array(
            'code' => $response['epp']['response']['result_attr']['code'],
            'msg' => $response['epp']['response']['result']['msg'],
            'reason' => $reason = (isset($response['epp']['response']['result']['extValue']['reason']) ? $response['epp']['response']['result']['extValue']['reason'] : '')
        );
        
        return $data;
    }

    /**
     * Checks if a domain is available.
     *
     * @param array $domains
     *            List of domains to check. Eg: array('test.com.br', 'test2.com.br').
     * @return array Returns information about the availability of domains.
     * @access public
     */
    public function domain_check($domains)
    {
        $xml = file_get_contents(__DIR__ . '/templates/br_domain_check.xml');
        
        $domains_list = '';
        foreach ($domains as $domain_name) {
            $domains_list .= "<domain:name>{$domain_name}</domain:name>";
        }
        
        $cltrid = '<clTRID>' . $this->generate_id() . '</clTRID>';
        
        $xml = str_replace('$(domains_list)$', $domains_list, $xml);
        $xml = str_replace('$(extension)$', '', $xml);
        $xml = str_replace('$(clTRID)$', $cltrid, $xml);
        
        $xml = $this->wrap($xml);
        $this->send_command($xml);
        $response = $this->xml2array($this->unwrap());
        
        if ($response['epp']['response']['result_attr']['code'] != '1000') {
            $reason = ($response['epp']['response']['result']['extValue']['reason'] ? ' - ' . $response['epp']['response']['result']['extValue']['reason'] : '');
            throw new \Exception($response['epp']['response']['result']['msg'] . $reason, $response['epp']['response']['result_attr']['code']);
        }
        
        $domain_cd_list = $response['epp']['response']['resData']['domain:chkData']['domain:cd'];
        // Se pesquisa só um domínio, o domain list não é uma lista de arrays (matriz), mas uma simples
        // lista. Então temos que verificar se o elemento [0] existe. Se sim é uma matriz, senão
        // é apenas uma lista simples então adicona ele numa array
        $domain_cd_list = (isset($domain_cd_list[0]) ? $domain_cd_list : array(
            $domain_cd_list
        ));
        $data = array();
        foreach ($domain_cd_list as $domain_cd) {
            $data[] = array(
                'domain_name' => $domain_cd['domain:name'],
                'avail' => ($domain_cd['domain:name_attr']['avail'] == '1'),
                'reason' => isset($domain_cd['domain:reason']) ? $domain_cd['domain:reason'] : ''
            );
        }
        return $data;
    }

    /**
     * Get information about a domain.
     *
     * @param string $domain_name
     *            Domain to look for. Eg: 'test.com.br'.
     * @param int $ticket_number
     *            Ticket number if a domain has one. Eg: '6489'. (Optional)
     *            
     * @return array Returns domain's information
     *        
     * @access public
     */
    public function domain_info($domain_name, $ticket_number = null)
    {
        $xml = file_get_contents(__DIR__ . '/templates/br_domain_info.xml');
        $auth_info = "<contact:authInfo>
						<contact:pw>{$this->getPassword()}</contact:pw>
						</contact:authInfo>";
        if ($ticket_number != null) {
            $extention = "
			<extension>
							<brdomain:info
							xmlns:brdomain=\"urn:ietf:params:xml:ns:brdomain-1.0\"
							xsi:schemaLocation=\"urn:ietf:params:xml:ns:brdomain-1.0
							brdomain-1.0.xsd\">
								<brdomain:ticketNumber>{$ticket_number}</brdomain:ticketNumber>
							</brdomain:info>
						</extension>";
            
            $xml = str_replace('$(extension)$', $extention, $xml);
        }
        $cltrid = '<clTRID>' . $this->generate_id() . '</clTRID>';
        
        $xml = str_replace('$(hosts_control)$', 'all', $xml);
        $xml = str_replace('$(name)$', $domain_name, $xml);
        $xml = str_replace('$(auth_info)$', $auth_info, $xml);
        $xml = str_replace('$(clTRID)$', $cltrid, $xml);
        
        $xml = $this->wrap($xml);
        
        $this->send_command($xml);
        
        $response = $this->xml2array($this->unwrap());
        
        if ($response['epp']['response']['result_attr']['code'] != '1000') {
            $reason = (isset($response['epp']['response']['result']['extValue']['reason']) ? ' - ' . $response['epp']['response']['result']['extValue']['reason'] : '');
            throw new \Exception($response['epp']['response']['result']['msg'] . $reason, $response['epp']['response']['result_attr']['code']);
        }
        
        $resData_InfData = $response['epp']['response']['resData']['domain:infData'];
        $extension_InfData = $response['epp']['response']['extension']['brdomain:infData'];
        $data = array(
            'code' => $response['epp']['response']['result_attr']['code'],
            'msg' => $response['epp']['response']['result']['msg'],
            'client_provider_id' => $resData_InfData['domain:clID'],
            'create_provider_id' => $resData_InfData['domain:crID'],
            'domain_name' => $resData_InfData['domain:name'],
            'domain_roid' => $resData_InfData['domain:roid'],
    		/* See EppBase->epp_status*/
    		'domain_publication_status' => (isset($resData_InfData['domain:status_attr']['s'])) ? $resData_InfData['domain:status_attr']['s'] : '',
            'domain_contact' => array(),
            'domain_doc' => array(),
            'domain_dns' => array(),
            'domain_create' => $resData_InfData['domain:crDate'],
            'domain_expiration' => (isset($resData_InfData['domain:exDate'])) ? $resData_InfData['domain:exDate'] : '',
    		/* See EppBase->epp_extension_status*/
    		'domain_extension_status' => $extension_InfData['brdomain:publicationStatus_attr']['publicationFlag'],
            'domain_extension_ticket' => $extension_InfData['brdomain:ticketNumber'],
            'domain_extension_organization' => $extension_InfData['brdomain:organization'],
            'domain_extension_autorenew' => $extension_InfData['brdomain:autoRenew_attr']['active'],
            'domain_extension_dns_pending' => array(),
            'domain_extension_doc_pending' => array()
        );
        // domain_contact
        foreach ($resData_InfData['domain:contact'] as $key => $contact) {
            if (! is_array($contact)) {
                $type = $resData_InfData['domain:contact']["{$key}_attr"]['type'];
                $data['domain_contact'][$type] = $contact;
            }
        }
        // domain_dns
        foreach ($resData_InfData['domain:ns']['domain:hostAttr'] as $key => $dns) {
            $hostName = (isset($dns['domain:hostName'])) ? $dns['domain:hostName'] : '';
            $hostAddr = (isset($dns['domain:hostAddr'])) ? $dns['domain:hostAddr'] : '';
            $hostType = (isset($dns['domain:hostAddr_attr']['ip'])) ? $dns['domain:hostAddr_attr']['ip'] : '';
            $data['domain_dns'][] = array(
                'host' => $hostName,
                'addr' => $hostAddr,
                'type' => $hostType
            );
        }
        // domain_dns_pending
        if (isset($extension_InfData['brdomain:pending']['brdomain:dns'])) {
            foreach ($extension_InfData['brdomain:pending']['brdomain:dns'] as $key => $dns) {
                $dnsHostName = isset($dns['brdomain:hostName']) ? $dns['brdomain:hostName'] : '';
                if ($dnsHostName) {
                    $dnsLimit = isset($dns['brdomain:limit']) ? $dns['brdomain:limit'] : '';
                    $dnsStatus = isset($extension_InfData['brdomain:pending']['brdomain:dns'][$key . '_attr']['status']) ? $extension_InfData['brdomain:pending']['brdomain:dns'][$key . '_attr']['status'] : '';
                    $dns_pending = array(
                        'host_name' => $dnsHostName
                    );
                    if ($dnsStatus) {
                        $dns_pending['status'] = $dnsStatus;
                    }
                    if ($dnsLimit) {
                        $dns_pending['limit'] = $dnsLimit;
                    }
                    $data['domain_extension_dns_pending'][] = $dns_pending;
                }
            }
        }
        // domain_doc
        $doc = isset($extension_InfData['brdomain:pending']['brdomain:doc']) ? $extension_InfData['brdomain:pending']['brdomain:doc'] : null;
        if ($doc) {
            $docStatus = isset($extension_InfData['brdomain:pending']['brdomain:doc_attr']['status']) ? $extension_InfData['brdomain:pending']['brdomain:doc_attr']['status'] : '';
            $docType = isset($doc['brdomain:docType']) ? $doc['brdomain:docType'] : '';
            $docLimit = isset($doc['brdomain:limit']) ? $doc['brdomain:limit'] : '';
            $docDescription = isset($doc['brdomain:description']) ? $doc['brdomain:description'] : '';
            $data['domain_extension_doc_pendig'][] = array(
                'status' => $docStatus,
                'type' => $docType,
                'limit' => $docLimit,
                'description' => $docDescription
            );
        }
        return $data;
    }

    /**
     * Creates a new domain.
     * This function creates a new domain.
     *
     * @param string $domain_name
     *            Domain name. Eg: 'test.com.br'.
     * @param int $domain_period
     *            Period for creation. For default is 1 year and does not accept another value.
     * @param string $dns_1
     *            Primary DNS in IPv4.
     * @param string $dns_2
     *            Secondary DNS in IPv4.
     * @param string $org_id
     *            Organization ID previously created. Eg: '246.838.523-30'.
     * @param bool $auto_renew
     *            1 for auto renew every year or 0 to expire til the end. Default is 0.
     *            
     * @return array Returns domain's information
     *        
     * @access public
     */
    public function domain_create($domain_name, $domain_period = 1, $dns_1 = null, $dns_2 = null, $org_id = null, $auto_renew = 0)
    {
        $xml = file_get_contents(__DIR__ . '/templates/br_domain_create.xml');
        
        $period = "<domain:period unit=\"y\">{$domain_period}</domain:period>";
        
        $nameservers = "<domain:ns>
							<domain:hostAttr>
								<domain:hostName>{$dns_1}</domain:hostName>
							</domain:hostAttr>
							<domain:hostAttr>
								<domain:hostName>{$dns_2}</domain:hostName>
							</domain:hostAttr>
						</domain:ns>";
        
        /**
         * This array is only used for Registro.br homologation because the homologation
         * requires an IPv6 message test.
         */
        $auth_info = "<contact:authInfo>
						<contact:pw>{$this->getPassword()}</contact:pw>
						</contact:authInfo>";
        
        $ext_begin = "<extension>
						<brdomain:create
						xmlns:brdomain=\"urn:ietf:params:xml:ns:brdomain-1.0\"
						xsi:schemaLocation=\"urn:ietf:params:xml:ns:brdomain-1.0
						brdomain-1.0.xsd\">
							<brdomain:organization>{$org_id}</brdomain:organization>
							<brdomain:autoRenew active=\"{$auto_renew}\"/>
						</brdomain:create>
					</extension>";
        
        $cltrid = '<clTRID>' . $this->generate_id() . '</clTRID>';
        
        $xml = str_replace('$(name)$', $domain_name, $xml);
        $xml = str_replace('$(period)$', $period, $xml);
        $xml = str_replace('$(nameservers)$', $nameservers, $xml);
        $xml = str_replace('$(registrant)$', '', $xml);
        $xml = str_replace('$(other_contacts)$', '', $xml);
        $xml = str_replace('$(auth_info)$', $auth_info, $xml);
        $xml = str_replace('$(ext_begin)$', $ext_begin, $xml);
        $xml = str_replace('$(ds_ext)$', '', $xml);
        $xml = str_replace('$(br_ext)$', '', $xml);
        $xml = str_replace('$(ext_end)$', '', $xml);
        $xml = str_replace('$(clTRID)$', $cltrid, $xml);
        
        $xml = $this->wrap($xml);
        
        $this->send_command($xml);
        
        $response = $this->xml2array($this->unwrap());
        
        if ($response['epp']['response']['result_attr']['code'] != '1000' && $response['epp']['response']['result_attr']['code'] != '1001') {
            $reason = (isset($response['epp']['response']['result']['extValue']['reason']) ? ' - ' . $response['epp']['response']['result']['extValue']['reason'] : '');
            throw new \Exception($response['epp']['response']['result']['msg'] . $reason, $response['epp']['response']['result_attr']['code']);
        }
        
        $creData = $response['epp']['response']['resData']['domain:creData'];
        $extension = $response['epp']['response']['extension']['brdomain:creData'];
        $data = array(
            'code' => $response['epp']['response']['result_attr']['code'],
            'msg' => $response['epp']['response']['result']['msg'],
            'domain_name' => $creData['domain:name'],
            'domain_create' => $creData['domain:crDate'],
            'domain_ticket' => $extension['brdomain:ticketNumber'],
            'domain_doc' => array(
                'description' => (isset($extension['brdomain:pending']['brdomain:doc']['brdomain:description'])) ? $extension['brdomain:pending']['brdomain:doc']['brdomain:description'] : '',
                'status' => (isset($extension['brdomain:pending']['brdomain:doc_attr']['status'])) ? $extension['brdomain:pending']['brdomain:doc_attr']['status'] : '',
                'type' => (isset($extension['brdomain:pending']['brdomain:doc']['brdomain:docType'])) ? $extension['brdomain:pending']['brdomain:doc']['brdomain:docType'] : ''
            ),
            'domain_dns' => array()
        );
        if (isset($extension['brdomain:pending']['brdomain:dns'])) {
            foreach ($extension['brdomain:pending']['brdomain:dns'] as $key => $dns) {
                $data['domain_dns'][] = array(
                    'host_name' => (isset($dns['brdomain:hostName'])) ? $dns['brdomain:hostName'] : '',
                    'limit' => (isset($dns['brdomain:limit'])) ? $dns['brdomain:limit'] : '',
                    'status' => (isset($extension['brdomain:pending']['brdomain:dns_attr'][$key]['status'])) ? $extension['brdomain:pending']['brdomain:dns_attr'][$key]['status'] : ''
                );
            }
        }
        return $data;
    }

    /**
     * Updates a domain.
     *
     * This function updates a domain's information.
     *
     * @param string $domain_name
     *            Domain name. Eg: 'test.com.br'.
     * @param string $dns_1
     *            Primary DNS in IPv4.
     * @param string $dns_2
     *            Secondary DNS in IPv4.
     * @param string $admin_id
     *            Contact's ID previously created. Eg: 'PTER1'.
     * @param string $billing_id
     *            Contact's ID previously created. Eg: 'PTER1'.
     * @param string $tech_id
     *            Contact's ID previously created. Eg: 'PTER1'.
     * @param bool $auto_renew
     *            1 for auto renew every year or 0 to expire til the end. Default is 0.
     *            
     * @return array Returns domain's information array( 'code' => 1010, 'msg' => 'message' )
     *        
     * @access public
     */
    public function domain_update($domain_name, $dns_1 = null, $dns_2 = null, $admin_id = null, $billing_id = null, $tech_id = null, $auto_renew = 0)
    {
        $domain_data = $this->domain_info($domain_name);
        
        $xml = file_get_contents(__DIR__ . '/templates/br_domain_update.xml');
        $chg = '';
        
        /**
         * This array is only used for Registro.br homologation because the homologation
         * requires 3 DNS verifications.
         */
        if($dns_1 && $dns_2){
            /*
             * The changes only happen if the both DNS are setted
             */
            $chg = "<domain:rem>
    					<domain:hostAttr>
    						<domain:hostName>{$domain_data['domain_dns'][0]['host']}</domain:hostName>
    					</domain:hostAttr>
    					<domain:hostAttr>
    						<domain:hostName>{$domain_data['domain_dns'][1]['host']}</domain:hostName>
    					</domain:hostAttr>
    				</domain:rem>
    				<domain:add>
    					<domain:hostAttr>
    						<domain:hostName>{$dns_1}</domain:hostName>
    					</domain:hostAttr>
    					<domain:hostAttr>
    						<domain:hostName>{$dns_2}</domain:hostName>
    					</domain:hostAttr>
    				</domain:add>";
        }
        
        if ($admin_id || $billing_id || $tech_id) {
            /*
             * Need at least one contact to make changes
             */
            $chg .= 
                "<domain:rem>"
                    .($admin_id ? "<domain:contact type=\"admin\">{$domain_data['domain_contact']['admin']}</domain:contact>" : '')
                    .($billing_id ? "<domain:contact type=\"billing\">{$domain_data['domain_contact']['billing']}</domain:contact>" : '')
                    .($tech_id ? "<domain:contact type=\"tech\">{$domain_data['domain_contact']['tech']}</domain:contact>" : '')
                    ."</domain:rem>
				  <domain:add>"
                    .($admin_id ? "<domain:contact type=\"admin\">{$admin_id}</domain:contact>" : '')
                    .($billing_id ? "<domain:contact type=\"billing\">{$billing_id}</domain:contact>" : '')
                    .($tech_id ? "<domain:contact type=\"tech\">{$tech_id}</domain:contact>" : '')
                 ."</domain:add>";
        }else{
            
        }
        
        $ext_begin = "<extension>
						<brdomain:update
						xmlns:brdomain=\"urn:ietf:params:xml:ns:brdomain-1.0\"
						xsi:schemaLocation=\"urn:ietf:params:xml:ns:brdomain-1.0
						brdomain-1.0.xsd\">
							<brdomain:chg>
								<brdomain:autoRenew active=\"{$auto_renew}\"/>
							</brdomain:chg>
						</brdomain:update>
					</extension>";
        
        $cltrid = '<clTRID>' . $this->generate_id() . '</clTRID>';
        
        $xml = str_replace('$(name)$', $domain_name, $xml);
        $xml = str_replace('$(add)$', '', $xml);
        $xml = str_replace('$(rem)$', '', $xml);
        $xml = str_replace('$(chg)$', $chg, $xml);
        $xml = str_replace('$(ext_begin)$', $ext_begin, $xml);
        $xml = str_replace('$(ds_ext)$', '', $xml);
        $xml = str_replace('$(br_ext)$', '', $xml);
        $xml = str_replace('$(ext_end)$', '', $xml);
        $xml = str_replace('$(clTRID)$', $cltrid, $xml);
        
        $xml = $this->wrap($xml);
        
        $this->send_command($xml);
        
        $response = $this->xml2array($this->unwrap());

        $reason = "";
        if(isset( $response['epp']['response']['result_attr']) ){
            if ($response['epp']['response']['result_attr']['code'] != '1000' && $response['epp']['response']['result_attr']['code'] != '1001') {
                if(isset($response['epp']['response']['result']['extValue']['reason']) ){
                    $reason = ' - ' . $response['epp']['response']['result']['extValue']['reason'];
                }else{
                    /*
                     * Sometimes we get a list of extValue
                     */
                    if(isset($response['epp']['response']['result']['extValue'])){
                        foreach ($response['epp']['response']['result']['extValue'] as $key => $extValue){
                            $reason .= " - ERROR$key: " . $extValue['reason'];
                        }
                    }//else is not reason
                }
                throw new \Exception($response['epp']['response']['result']['msg'] . $reason, $response['epp']['response']['result_attr']['code']);
            }
        }else{
            /*
             * if response['epp']['response']['result_attr'] does not exists is because $response['epp']['response']['result'] is a list 
             */
            $msg = '';
            $code = '0';
            if(isset($response['epp']['response']['result'])){
                foreach ($response['epp']['response']['result'] as $key => $result){
                    if(is_int($key) ){
                        $reason .= " - ERROR$key: " . $result['extValue']['reason'];
                        if($key == 0){
                            $msg = $result['msg'];
                        }
                    }elseif (strpos($key,'_attr')!==false){
                        $code = $result['code'];
                    }
                }
            }
            throw new \Exception($msg . $reason, $code);
        }
        
        $data = array(
            'code' => $response['epp']['response']['result_attr']['code'],
            'msg' => $response['epp']['response']['result']['msg'],
            'reason' => $reason = (isset($response['epp']['response']['result']['extValue']['reason']) ? $response['epp']['response']['result']['extValue']['reason'] : '')
        );
        
        return $data;
    }

    /**
     * Updates the domain expiration date.
     * When a domain is expiring, this function will update the expiration date for 1, 2 or more years.
     *
     * @param string $domain_name
     *            Domain name. Ex: 'test.com.br'.
     * @param string $domain_expiration
     *            Current expiration date. You can find out the expiration date
     *            using domain_info().
     * @param int $domain_year_renovation
     *            Number of years to renovate. Default is 1 year.
     * @return array Returns domain's information. Array( 'code' => 1010, 'msg' => 'message' )
     * @access public
     */
    public function domain_renew($domain_name, $domain_expiration, $domain_year_renovation = 1)
    {
        $xml = file_get_contents(__DIR__ . '/templates/domain_renew.xml');
        
        $period = "<domain:period unit=\"y\">{$domain_year_renovation}</domain:period>";
        
        $cltrid = '<clTRID>' . $this->generate_id() . '</clTRID>';
        
        $xml = str_replace('$(name)$', $domain_name, $xml);
        $xml = str_replace('$(curExpDate)$', $domain_expiration, $xml);
        $xml = str_replace('$(period)$', $period, $xml);
        $xml = str_replace('$(clTRID)$', $cltrid, $xml);
        
        $xml = $this->wrap($xml);
        
        $this->send_command($xml);
        
        $response = $this->xml2array($this->unwrap());
        
        if ($response['epp']['response']['result_attr']['code'] != '1000') {
            $reason = ($response['epp']['response']['result']['extValue']['reason'] ? ' - ' . $response['epp']['response']['result']['extValue']['reason'] : '');
            throw new \Exception($response['epp']['response']['result']['msg'] . $reason, $response['epp']['response']['result_attr']['code']);
        }
        
        $data = array(
            'domain_name' => $response['epp']['response']['resData']['domain:renData']['domain:name'],
            'domain_new_expiration' => $response['epp']['response']['resData']['domain:renData']['domain:exDate'],
            'domain_publication_status' => (isset($response['epp']['response']['extension']['brdomain:renData']['brdomain:publicationStatus_attr']['publicationFlag'])) ? $response['epp']['response']['extension']['brdomain:renData']['brdomain:publicationStatus_attr']['publicationFlag'] : ''
        );
        return $data;
    }

    /**
     * Deletes a domain.
     *
     * This function deletes a domain. But there are restrictions for delete. You can only delete
     * domains created up to X days. Consult Registro.br for more details.
     *
     * @param string $domain_name
     *            Domain name. Eg: 'test.com.br'.
     * @return array Returns domain's information.
     * @access public
     */
    public function domain_delete($domain_name = null)
    {
        $xml = file_get_contents(__DIR__ . '/templates/domain_delete.xml');
        
        $cltrid = '<clTRID>' . $this->generate_id() . '</clTRID>';
        
        $xml = str_replace('$(name)$', $domain_name, $xml);
        $xml = str_replace('$(clTRID)$', $cltrid, $xml);
        
        $xml = $this->wrap($xml);
        
        $this->send_command($xml);
        
        $response = $this->xml2array($this->unwrap());
        if ($response['epp']['response']['result_attr']['code'] != '1000' && $response['epp']['response']['result_attr']['code'] != '1001') {
            $reason = ($response['epp']['response']['result']['extValue']['reason'] ? ' - ' . $response['epp']['response']['result']['extValue']['reason'] : '');
            throw new \Exception($response['epp']['response']['result']['msg'] . $reason, $response['epp']['response']['result_attr']['code']);
        }
        
        $data = array(
            'code' => $response['epp']['response']['result_attr']['code'],
            'msg' => $response['epp']['response']['result']['msg'],
            'reason' => $reason = ($response['epp']['response']['result']['extValue']['reason'] ? $response['epp']['response']['result']['extValue']['reason'] : '')
        );
        
        return $data;
    }

    /**
     * Reads the last message from the Registro.br's EPP system.
     *
     * This function reads the last message in the queue. Registro.br will get in touch with
     * your company through messages POLL.
     * Important messages will be read with this function.
     *
     * @return array Returns the last message in the queue.
     * @access public
     */
    public function poll_request()
    {
        $xml = file_get_contents(__DIR__ . '/templates/poll.xml');
        
        $cltrid = '<clTRID>' . $this->generate_id() . '</clTRID>';
        
        $xml = str_replace('$(op)$', 'req', $xml);
        $xml = str_replace('$(msgID)$', '', $xml);
        $xml = str_replace('$(clTRID)$', $cltrid, $xml);
        
        $xml = $this->wrap($xml);
        
        $this->send_command($xml);
        
        $response = $this->xml2array($this->unwrap());
        
        if ($response['epp']['response']['result_attr']['code'] != '1000' && $response['epp']['response']['result_attr']['code'] != '1301') {
            $reason = ($response['epp']['response']['result']['extValue']['reason'] ? ' - ' . $response['epp']['response']['result']['extValue']['reason'] : '');
            throw new \Exception($response['epp']['response']['result']['msg'] . $reason, $response['epp']['response']['result_attr']['code']);
        }
        
        $data = array(
            'msg_count' => $response['epp']['response']['msgQ_attr']['count'],
            'msg_id' => $response['epp']['response']['msgQ_attr']['id'],
            'msg_date' => $response['epp']['response']['msgQ']['qDate'],
            'msg_content' => (isset($response['epp']['response']['msgQ']['msg'])) ? $response['epp']['response']['msgQ']['msg'] : ''
        );
        
        return $data;
    }

    /**
     * Deletes a message from the Registro.br's EPP system.
     *
     * This function deletes one message from the Registro.br's EPP system. You need the message ID, which
     * can be retrieve from poll_request() function.
     *
     * @param int $message_id
     *            Mensagem a ser apagada. Ex: '4680'.
     * @return array Returns information about the deleted mesage. array( 'code' => 1010, 'msg' => 'message' )
     * @access public
     */
    public function poll_delete($message_id = null)
    {
        $xml = file_get_contents(__DIR__ . '/templates/poll.xml');
        
        $cltrid = '<clTRID>' . $this->generate_id() . '</clTRID>';
        
        $xml = str_replace('$(op)$', 'ack', $xml);
        $xml = str_replace('$(msgID)$', "msgID=\"{$message_id}\"", $xml);
        $xml = str_replace('$(clTRID)$', $cltrid, $xml);
        
        $xml = $this->wrap($xml);
        
        $this->send_command($xml);
        
        $response = $this->xml2array($this->unwrap());
        
        if ($response['epp']['response']['result_attr']['code'] != '1000' && $response['epp']['response']['result_attr']['code'] != '1001') {
            $reason = ($response['epp']['response']['result']['extValue']['reason'] ? ' - ' . $response['epp']['response']['result']['extValue']['reason'] : '');
            throw new \Exception($response['epp']['response']['result']['msg'] . $reason, $response['epp']['response']['result_attr']['code']);
        }
        
        $data = array(
            'code' => $response['epp']['response']['result_attr']['code'],
            'msg' => $response['epp']['response']['result']['msg'],
            'reason' => $reason = ($response['epp']['response']['result']['extValue']['reason'] ? $response['epp']['response']['result']['extValue']['reason'] : '')
        );
        
        return $data;
    }
}
