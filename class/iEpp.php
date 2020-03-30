<?php
namespace NicBr\Epp;

/**
 * EPP Interface
 * Interface for all functions EPP class must implement.
 * PHP version 5.4
 *
 * @author Diogo Tozzi <diogo@diogotozzi.com>
 * @copyright 2012 - Diogo Tozzi
 * @link http://github.com/diogotozzi/Epp
 * @version 1.0
 */
interface iEpp
{

    public function contact_info($client_id = null);

    public function contact_create($client_name = null, $client_street_1 = null, $client_street_2 = null, $client_street_3 = null, $client_city = null, $client_state = null, $client_zipcode = null, $client_country = 'BR', $client_phone = null, $client_email = null);

    public function contact_update($client_id = null, $client_street_1 = null, $client_street_2 = null, $client_street_3 = null, $client_city = null, $client_state = null, $client_zipcode = null, $client_country = 'BR', $client_phone = null, $client_email = null);

    public function org_check($org_id = null);

    public function org_info($org_id = null);

    public function org_create($org_id, $org_name = null, $org_street_1 = null, $org_street_2 = null, $org_street_3 = null, $org_city = null, $org_state = null, $org_zipcode = null, $org_country = 'BR', $org_phone = null, $org_email = null, $contact_admin_id = null, $contact_tech_id = null, $contact_billing_id = null, $responsible = null);

    public function org_update($org_id, $org_name = null, $org_street_1 = null, $org_street_2 = null, $org_street_3 = null, $org_city = null, $org_state = null, $org_zipcode = null, $org_country = 'BR', $org_phone = null, $contact_list = null, $responsible = null);

    public function domain_check($domains);

    public function domain_info($domain_name, $ticket_number = null);

    public function domain_create($domain_name, $domain_period = 1, $dns_1 = null, $dns_2 = null, $org_id = null, $auto_renew = 0);

    public function domain_update($domain_name, $dns_1 = null, $dns_2 = null, $admin_id = null, $billing_id = null, $tech_id = null, $auto_renew = 0);

    public function domain_renew($domain_name, $domain_expiration, $domain_year_renovation = 1);

    public function domain_delete($domain_name = null);

    public function poll_request();

    public function poll_delete($message_id = null);
}
