<?php

declare(strict_types=1);

namespace ToddMinerTech\ApptivoPhp;

use ToddMinerTech\ApptivoPhp\AppParams;
use ToddMinerTech\ApptivoPhp\ObjectCrud;
use ToddMinerTech\ApptivoPhp\ObjectDataUtils;
use ToddMinerTech\ApptivoPhp\ObjectTableUtils;
use ToddMinerTech\ApptivoPhp\SystemUtils;
use GuzzleHttp\Psr7\Request;

/**
 * Class ApptivoController
 *
 * Controls all Apptivo API queries and data handling functions
 *
 * @package ToddMinerTech\apptivo-php-mt
 */
class ApptivoController
{
    /**  @var string $apiKey API Key for the business to be accessed */
    private $apiKey;
    /**  @var string $accessKey Access Key for the business to be accessed */
    private $accessKey;
    /**  @var string $apiUserEmail Email address of the employee who we should perform actions on behalf of */
    private $apiUserNameStr;
    /**  @var string $sessionEmailId Email address of the session we authenticated */
    public $sessionEmailId;
    /**  @var string $sessionPassword Matching password for session email */
    public $sessionPassword;
    /**  @var string $firmId Firm id for the session authentication */
    public $firmId;
    /**  @var string $sessionKey Session key from authentication */
    //IMPROVEMENT do get/set and privatize later
    public $sessionKey = '';
    /**  @var array $configDataArr Stores an array of json config data objects queried from API to prevent multiple queries */
    private $configDataArr = [];
    /**  @var int $apiSleepTime The global wait time to be applied before executing an api call.  Prevents rate limiting by the Apptivo API. */
    public $apiSleepTime = 1;
    /**  @var int $apiRetries The global number of retries to apply when an api call appears to fail.  This helps recover from getting rate limimted. */
    public $apiRetries = 1;
    
    function __construct(string $apiKey, string $accessKey, string $apiUserEmail) {
        $this->apiKey = $apiKey;
        $this->accessKey = $accessKey;
        if($apiUserEmail) {
            $this->apiUserNameStr = '&userName='.$apiUserEmail;
        }else{
            $this->apiUserNameStr = '';
        }
    }
    
    /* 
     * setSessionCredentials Most endpoints work fine with api/access key authentication, but some require a sessionKey.
     * Load in these values securely, then call SystemUtils::setSessionKey to authenticate and store a session key.
     */
    public function setSessionCredentials(string $sessionEmailId, string $password, string $firmId): void
    {
        $this->sessionEmailId = $sessionEmailId;
        $this->sessionPassword = $password;
        $this->sessionFirmId = $firmId;
        SystemUtils::setSessionKey($this);
    }
    
    /* ObjectCrud 
     * 
     */
    public function read(string $appNameOrId, string $objectId): object 
    {
        return ObjectCrud::read($appNameOrId, $objectId, $this);
    }
    
    /* ObjectDataUtils 
     * 
     */    
    public function getConfigData(string $appNameOrId): object
    {
        return ObjectDataUtils::getConfigData($appNameOrId, $this);
    }
    
    public function getAttrDetailsFromLabel(array $fieldLabel, object $inputObj, string $appNameOrId): ResultObject 
    {
        $configData = $this->getConfigData($appNameOrId);
        return ObjectDataUtils::getAttrDetailsFromLabel($fieldLabel, $inputObj, $configData);
    }
    
    public function getAttrSettingsObjectFromLabel(array $fieldLabel, string $appNameOrId): ResultObject 
    {
        $configData = $this->getConfigData($appNameOrId);
        return ObjectDataUtils::getSettingsAttrObjectFromLabel($fieldLabel, $configData);
    }
    
    public function createNewAttrObjFromLabelAndValue(array $fieldLabel, array $newValue, string $appNameOrId): object 
    {
        $configData = $this->getConfigData($appNameOrId);
        return ObjectDataUtils::createNewAttrObjFromLabelAndValue($fieldLabel, $newValue, $configData);
    }
    
    public function setAssociatedFieldValues(string $tagName, string $newValue, object &$object, string $appNameOrId): ResultObject
    {
        $configData = $this->getConfigData($appNameOrId);
        return ObjectDataUtils::setAssociatedFieldValues($tagName, $newValue, $object, $appNameOrId, $configData, $this);
    }
    public static function getAddressValueFromTypeAndField(string $addressType, string $addressFieldName, object $sourceModelObj): string
    {
        return ObjectDataUtils::getAddressValueFromTypeAndField($addressType, $addressFieldName, $sourceModelObj);
    }
    
    /* ObjectTableUtils 
     * 
     */  
    public function getTableSectionRowsFromSectionLabel(string $sectionLabel, object $objectData, string $appNameOrId): ?array
    {
        $configData = $this->getConfigData($appNameOrId);
        $tableSectionId = ObjectTableUtils::getTableSectionAttributeIdFromLabel($sectionLabel, $configData);
        return self::getTableSectionRowsFromSectionId($tableSectionId, $objectData);
    }
    public static function getTableRowColIndexFromAttributeId(string $customAttributeId, object $tableRowObj): ?int
    {
        return ObjectTableUtils::getTableRowColIndexFromAttributeId($customAttributeId, $tableRowObj);
    }  
    public static function getTableSectionRowsFromSectionId(string $tableSectionId, object $objectData): ?array
    {
        return ObjectTableUtils::getTableSectionRowsFromSectionId($tableSectionId, $objectData);
    }  
    public function getTableRowAttrValueFromLabel(string $inputLabel, object $inputRowObj, string $appNameOrId): ?string
    {
        $configData = $this->getConfigData($appNameOrId);
        return ObjectTableUtils::getTableRowAttrValueFromLabel($inputLabel, $inputRowObj, $configData);
    }
    
    /* SearchUtils 
     * 
     */  
    public function getAllBySearchText(string $searchText, string $appNameOrId): array
    {
        return SearchUtils::getAllBySearchText($searchText, $appNameOrId, $this);
    }
    
    public function getEmployeeIdFromName(string $employeeNameToFind): string
    {
        return SearchUtils::getEmployeeIdFromName($employeeNameToFind, $this);
    }
    
    public function getCustomerObjFromName(string $customerNameToFind): object
    {
        return SearchUtils::getCustomerObjFromName($customerNameToFind, $this);
    }
    
    public function getCustomerIdFromName(string $customerNameToFind): string
    {
        return SearchUtils::getCustomerIdFromName($customerNameToFind, $this);
    }
    public function getAllRecordsInApp(string $appNameOrId, int $maxRecords = 20000): array
    {
        return SearchUtils::getAllRecordsInApp($appNameOrId, $this, $maxRecords);
    }
    public function getObjectFromKeywordSearchAndCriteria(array $fieldToMatch, string $valueToMatch, string $appNameOrId): ResultObject
    {
        return SearchUtils::getObjectFromKeywordSearchAndCriteria($fieldToMatch, $valueToMatch, $appNameOrId, $this);
    }
    
    /* Get/Set 
     * 
     */  
    public function getApiKey(): string
    {
        return $this->apiKey;
    }
    public function getAccessKey(): string
    {
        return $this->accessKey;
    }
    public function getUserNameStr(): string
    {
        return $this->apiUserNameStr;
    }
    public function getConfigDataArr(): array
    {
        return $this->configDataArr;
    }
    public function setConfigDataArr(array $newConfigDataArr): void
    {
        $this->configDataArr = $newConfigDataArr;
    }
}
