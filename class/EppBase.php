<?php
namespace NicBr\Epp;

/**
 * EPP Abstract Class
 *
 * This class contains the basic methods to connect and interact with Registro.br
 *
 * PHP version 5.4
 *
 * @author Diogo Tozzi <diogo@diogotozzi.com>
 * @copyright 2012 - Diogo Tozzi
 * @link http://github.com/diogotozzi/Epp
 * @version 1.0
 */
abstract class EppBase
{

    /**
     * Registro.br host address
     *
     * @var string
     * @access protected
     */
    private $_host = 'beta.registro.br';

    /**
     * Registro.br port
     *
     * @var int
     * @access protected
     */
    private $_port = 700;

    /**
     * Socket to conect via TCP
     *
     * @var resource
     * @access protected
     */
    protected $_socket = null;

    /**
     * Se verdadeiro está conectado
     * 
     * @var boolean
     */
    private $connected;

    /**
     * Se verdadeiro está logado
     * 
     * @var boolean
     */
    private $loged;

    /**
     * Cert file for SSL connection
     *
     * @var string
     * @access protected
     */
    private $_cert = '/certs/client.pem';

    /**
     * Cert Pass phase
     *
     * @var string
     * @access protected
     */
    private $_passphrase = '';

    /**
     * Account password in Registro.br
     *
     * @var string
     * @access protected
     */
    private $_user = '';

    /**
     * Account password in Registro.br
     *
     * @var string
     * @access protected
     */
    private $_password = '';

    private $epp_error_codes;

    private $epp_success_codes;

    private $epp_status;

    private $epp_extension_status;

    function __construct()
    {
        $this->epp_error_codes = array(
            '2000' => _("Unknown command"),
            '2001' => _("Command syntax error"),
            '2002' => _("Command use error"),
            '2003' => _("Required parameter missing"),
            '2004' => _("Parameter value range error"),
            '2005' => _("Parameter value syntax error"),
            '2100' => _("Unimplemented protocol version"),
            '2101' => _("Unimplemented command"),
            '2102' => _("Unimplemented option"),
            '2103' => _("Unimplemented extension"),
            '2104' => _("Billing failure"),
            '2105' => _("Object is not eligible for renewal"),
            '2106' => _("Object is not eligible for transfer"),
            '2200' => _("Authentication error"),
            '2201' => _("Authorization error"),
            '2202' => _("Invalid authorization information"),
            '2300' => _("Object pending transfer"),
            '2301' => _("Object not pending transfer"),
            '2302' => _("Object exists"),
            '2303' => _("Object does not exist"),
            '2304' => _("Object status prohibits operation"),
            '2305' => _("Object association prohibits operation"),
            '2306' => _("Parameter value policy error"),
            '2307' => _("Unimplemented object service"),
            '2308' => _("Data management policy violation"),
            '2400' => _("Command failed"),
            '2500' => _("Command failed, server closing connection"),
            '2501' => _("Authentication error, server closing connection"),
            '2502' => _("Session limit exceeded, server closing connection")
        );
        
        $this->epp_success_codes = array(
            '1000' => _("Command completed successfully"),
            '1001' => _("Command completed successfully; action pending"),
            '1300' => _("Command completed successfully; no messages"),
            '1301' => _("Command completed successfully; ack to dequeue"),
            '1500' => _("Command completed successfully; ending session")
        );
        
        $this->epp_status = array(
            "clientDeleteProhibited" => 'abort',
            "clientHold" => 'retry',
            "clientRenewProhibited" => 'abort',
            "clientTransferProhibited" => 'abort',
            "clientUpdateProhibited" => 'abort',
            "inactive" => 'retry',
            "ok" => 'finish',
            "pendingCreate" => 'retry',
            "pendingDelete" => 'retry',
            "pendingRenew" => 'retry',
            "pendingTransfer" => 'retry',
            "pendingUpdate" => 'retry',
            "serverDeleteProhibited" => 'abort',
            "serverHold" => 'retry',
            "serverRenewProhibited" => 'abort',
            "serverTransferProhibited" => 'abort',
            "serverUpdateProhibited" => 'abort'
        );
        
        $this->epp_extension_status = array(
            "published" => 'finish',
            "waitingPublication" => 'retry',
            "onHold" => 'retry',
            "waitingWithdrawal" => 'retry'
        );
    }

    /**
     * Get Error message
     * 
     * @param string $code
     *            Success code, like '1000'
     * @return string Message
     */
    public function getEppErrorMessage($code)
    {
        $message = isset($this->epp_error_codes[$code]) ? $this->epp_error_codes[$code] : _("Code '$code' does not exists");
        return $message;
    }

    /**
     * Get Success message
     * 
     * @param string $code
     *            Success code, like '2303'
     * @return string Message
     */
    public function getEppSuccessMessage($code)
    {
        $message = isset($this->epp_success_codes[$code]) ? $this->epp_success_codes[$code] : _("Code '$code' does not exists");
        return $message;
    }

    /**
     * Get publication status
     * 
     * @param string $code
     *            Success code, like 'ok', 'clientHold', 'inactive', etc
     * @return string Action can be 'retry', 'abort', 'finish' or null if code doesn't exist
     */
    public function getEppPublicationStatus($code)
    {
        $message = isset($this->epp_status[$code]) ? $this->epp_status[$code] : null;
        return $message;
    }

    /**
     * Get extension publication status
     * 
     * @param string $code
     *            Success code, like 'published', 'waitingPublication', 'onHold' or 'waitingWithdrawal'
     * @return string Action can be 'retry', 'abort', 'finish' or null if code doesn't exist
     */
    public function getEppExtensionPublicationStatus($code)
    {
        $message = isset($this->epp_extension_status[$code]) ? $this->epp_extension_status[$code] : null;
        return $message;
    }

    /**
     * Cria um socket
     * 
     * @throws \Exception
     * @return string
     */
    public function connect()
    {
        $errno = null;
        $errstr = null;
        $fc = stream_context_create(array(
            'ssl' => array(
                'allow_self_signed' => true,
                'local_cert' => $this->getCert(),
                'passphrase' => $this->getPassphrase(),
                'verify_peer' => false
            )
        ));
        
        if (! $this->_socket = stream_socket_client("tls://{$this->getHost()}:{$this->getPort()}", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $fc)) {
            $this->setConnected(false);
            throw new \Exception("Error ($errno): '$errstr'");
        }
        $this->setConnected(true);
        return $this->unwrap();
    }

    /**
     * Encerra uma connexão
     */
    public function disconnect()
    {
        // Close socket
        if ($this->isConnected()) {
            $this->setConnected(false);
            fclose($this->_socket);
        }
    }

    protected function generate_id()
    {
        return mt_rand(00000, 99999);
    }

    /**
     * Empacota uma string xml em uma string binária para enviar via socket
     * 
     * @param string $xml
     *            Nome do template xml
     * @return string
     */
    protected function wrap($xml = null)
    {
        return pack('N', (strlen($xml) + 4)) . $xml;
    }

    /**
     * Lê os dados de um socket e converte de binário para array php
     * 
     * @throws \Exception Em caso de erro ou timeout
     * @return string Dados lidos
     */
    protected function unwrap()
    {
        if (feof($this->_socket))
            throw new \Exception("Socket pointer is at EOF or an error occurs (including socket timeout");
        
        $packet_header = fread($this->_socket, 4);
        
        if (empty($packet_header)) {
            throw new \Exception("Header packet is empty");
        } else {
            $unpacked = unpack('N', $packet_header);
            $answer = fread($this->_socket, $unpacked[1] - 4);
        }
        return $answer;
    }

    protected function send_command($xml = null)
    {
        return fwrite($this->_socket, $xml);
    }

    /**
     * Efetua o login
     * 
     * @param string $new_password
     * @param string $language
     * @return string
     */
    public function login($new_password = null, $language = 'pt')
    {
        if (! $this->isLoged()) {
            $xml = file_get_contents(__DIR__ . '/templates/login.xml');
            
            if (strlen($new_password) >= 5) {
                $xml = str_replace('$(newPW)$', "<newPW>$new_password</newPW>", $xml);
            } else {
                $xml = str_replace('$(newPW)$', '', $xml);
            }
            
            $xml = str_replace('$(clID)$', $this->getUser(), $xml);
            $xml = str_replace('$(pw)$', $this->getPassword(), $xml);
            $xml = str_replace('$(lang)$', $language, $xml);
            $xml = str_replace('$(clTRID)$', '<clTRID>' . $this->generate_id() . '</clTRID>', $xml);
            
            $xml = $this->wrap($xml);
            
            $this->send_command($xml);
            $this->setLoged(true);
            $result = $this->unwrap();
        } else {
            $result = true;
        }
        return $result;
    }

    /**
     * Efetuar o logout
     * 
     * @return string
     */
    public function logout()
    {
        if ($this->isLoged()) {
            $xml = file_get_contents(__DIR__ . '/templates/logout.xml');
            
            $xml = str_replace('$(clTRID)$', '<clTRID>' . $this->generate_id() . '</clTRID>', $xml);
            
            $xml = $this->wrap($xml);
            
            $this->send_command($xml);
            $this->setLoged(false);
            $result = $this->unwrap();
        } else {
            $result = null;
        }
        return $result;
    }

    public function hello()
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n";
        $xml .= "<epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\">\n";
        $xml .= "<hello/>\n";
        $xml .= "</epp>";
        
        $xml = $this->wrap($xml);
        
        $this->send_command($xml);
        
        return $this->unwrap();
    }

    /**
     * Converte um xml em array
     * 
     * @param string $contents
     * @param number $get_attributes
     * @param string $priority
     * @return void|array
     */
    public function xml2array($contents, $get_attributes = 1, $priority = 'tag')
    {
        if (! function_exists('xml_parser_create'))
            return array();
        
        $parser = xml_parser_create('');
        
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($contents), $xml_values);
        xml_parser_free($parser);
        
        if (! $xml_values)
            return; // Hmm...
        
        $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();
        $current = &$xml_array;
        $repeated_tag_index = array();
        
        foreach ($xml_values as $data) {
            unset($attributes, $value);
            
            extract($data);
            
            $result = array();
            $attributes_data = array();
            
            if (isset($value)) {
                if ($priority == 'tag')
                    $result = $value;
                else
                    $result['value'] = $value;
            }
            if (isset($attributes) && $get_attributes) {
                foreach ($attributes as $attr => $val) {
                    if ($priority == 'tag')
                        $attributes_data[$attr] = $val;
                    else
                        $result['attr'][$attr] = $val; // Set all the attributes in a array called 'attr'
                }
            }
            if ($type == 'open') {
                $parent[$level - 1] = &$current;
                
                if (! is_array($current) or (! in_array($tag, array_keys($current)))) {
                    $current[$tag] = $result;
                    
                    if ($attributes_data)
                        $current[$tag . '_attr'] = $attributes_data;
                    
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    $current = & $current[$tag];
                } else {
                    if (isset($current[$tag][0])) {
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                        $repeated_tag_index[$tag . '_' . $level] ++;
                    } else {
                        $current[$tag] = array(
                            $current[$tag],
                            $result
                        );
                        $repeated_tag_index[$tag . '_' . $level] = 2;
                        
                        if (isset($current[$tag . '_attr'])) {
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset($current[$tag . '_attr']);
                        }
                    }
                    
                    $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
                    $current = &$current[$tag][$last_item_index];
                }
            } elseif ($type == 'complete') {
                if (! isset($current[$tag])) {
                    $current[$tag] = $result;
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' && $attributes_data)
                        $current[$tag . '_attr'] = $attributes_data;
                } else {
                    if (isset($current[$tag][0]) && is_array($current[$tag])) {
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                        if ($priority == 'tag' && $get_attributes && $attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                        }
                        $repeated_tag_index[$tag . '_' . $level] ++;
                    } else {
                        $current[$tag] = array(
                            $current[$tag],
                            $result
                        );
                        $repeated_tag_index[$tag . '_' . $level] = 1;
                        
                        if ($priority == 'tag' && $get_attributes) {
                            if (isset($current[$tag . '_attr'])) {
                                $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                                unset($current[$tag . '_attr']);
                            }
                            if ($attributes_data) {
                                $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                            }
                        }
                        $repeated_tag_index[$tag . '_' . $level] ++; // 0 && 1 index is already taken
                    }
                }
            } elseif ($type == 'close') {
                $current = & $parent[$level - 1];
            }
        }
        return ($xml_array);
    }

    /**
     *
     * @return string
     */
    public function getHost()
    {
        return $this->_host;
    }

    /**
     *
     * @return number
     */
    public function getPort()
    {
        return $this->_port;
    }

    /**
     *
     * @return string
     */
    public function getCert()
    {
        return $this->_cert;
    }

    /**
     *
     * @return string
     */
    public function getPassphrase()
    {
        return $this->_passphrase;
    }

    /**
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /**
     *
     * @param string $_host
     */
    public function setHost($_host)
    {
        $this->_host = $_host;
    }

    /**
     *
     * @param number $_port
     */
    public function setPort($_port)
    {
        $this->_port = $_port;
    }

    /**
     *
     * @param string $_cert
     */
    public function setCert($_cert)
    {
        $this->_cert = $_cert;
    }

    /**
     *
     * @param string $_passphrase
     */
    public function setPassphrase($_passphrase)
    {
        $this->_passphrase = $_passphrase;
    }

    /**
     *
     * @param string $_password
     */
    public function setPassword($_password)
    {
        $this->_password = $_password;
    }

    /**
     *
     * @return string
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     *
     * @param string $_user
     */
    public function setUser($_user)
    {
        $this->_user = $_user;
    }

    /**
     *
     * @return boolean
     */
    public function isConnected()
    {
        return $this->connected;
    }

    /**
     *
     * @return boolean
     */
    public function isLoged()
    {
        return $this->loged;
    }

    /**
     *
     * @param boolean $isConnected
     */
    public function setConnected($isConnected)
    {
        $this->connected = $isConnected;
    }

    /**
     *
     * @param boolean $isLoged
     */
    public function setLoged($isLoged)
    {
        $this->loged = $isLoged;
    }
}
