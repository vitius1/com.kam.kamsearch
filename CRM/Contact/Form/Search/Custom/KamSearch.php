<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Contact_Form_Search_Custom_KamSearch extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_formValues;
  public $_from;

  /**
   * Class constructor.
   *
   * @param array $formValues
   */
  public function __construct(&$formValues) {
    $this->_formValues = $formValues;

    // Define the columns for search result rows
    $this->_columns = array(
      'Jméno' => 'sort_name',
      'Email' => 'email',
      'Telefon' => 'phone',
      'Země' => 'country',
      'Adresa' => 'address',
    );
  }

  /**
   * Build the form.
   *
   * The form consists of an autocomplete field to select an organization.
   */
  public function buildForm(&$form) {
    // add select for groups
    $group = CRM_Core_PseudoConstant::nestedGroup();
    
    $form->addElement('text', 'name', ts('Jméno nebo email'));
    $form->addElement('select', 'type', ts('Druh kontaktu'), [], ["id" => "custom-loading-1", 'class' => 'crm-select2 huge','multiple' => 'multiple','placeholder' => 'loading...', 'disabled']);
    $form->addElement('select', 'group', ts('Je ve skupině'), $group, ['class' => 'crm-select2 huge','multiple' => 'multiple','placeholder' => '- je ve skupině -']);
    $form->addElement('select', 'country', ts('Země'), ['' => 'loading...'], ["id" => "custom-loading-2", 'class' => 'crm-select2 huge', 'disabled', 'onChange'=>'CountryChange(this.value)']);
    $form->addElement('select', 'kraj', ts('Kraj'), [], ["id" => "custom-loading-3", 'class' => 'crm-select2 huge','multiple' => 'multiple','placeholder' => '- zvolte první zemi -', 'disabled']);
    $form->addElement('text', 'address_name', ts('Název adresy'));
    $form->addElement('select', 'kam_region', ts('KAM region'), [], ["id" => "custom-loading-4", 'class' => 'crm-select2 huge','multiple' => 'multiple','placeholder' => 'loading...', 'disabled']);
    $form->addElement('select', 'denomination', ts('Církev/Denominace'), [], ["id" => "custom-loading-5", 'class' => 'crm-select2 huge','multiple' => 'multiple','placeholder' => 'loading...', 'disabled']);
    $form->addElement('select', 'relationship', ts('Má vztah'), [], ["id" => "custom-loading-6", 'class' => 'crm-select2 huge','multiple' => 'multiple','placeholder' => 'loading...', 'disabled']);
    $form->addElement('select', 'event', ts('Účastník události'), [], ["id" => "custom-loading-7", 'class' => 'crm-select2 huge','multiple' => 'multiple','placeholder' => 'loading...', 'disabled']);
    
    $form->addElement('checkbox', 'check_email', "Email", '', ['class' => 'crm-form-checkbox']);
    $form->addElement('checkbox', 'check_phone', "Telefon", '', ['class' => 'crm-form-checkbox']);
    $form->addElement('checkbox', 'check_country', "Země", '', ['class' => 'crm-form-checkbox']);
    $form->addElement('checkbox', 'check_address', "Adresa", '', ['class' => 'crm-form-checkbox']);
    
    $form->setDefaults(array('check_email'=>'1'));
    $form->setDefaults(array('check_phone'=>'1'));
    $form->setDefaults(array('check_country'=>'1'));
    $form->setDefaults(array('check_address'=>'1'));
    
    
    $form->addElement('hidden', 'id');

    $this->setTitle('Vyhledávání');

    $form->assign('elements', array('name', 'type', 'group', 'country', 'kraj', 'address_name', 'kam_region', 'denomination', 'relationship', 'event'
    , 'check_email', 'check_phone', 'check_country', 'check_address', 'id'));
  }

  /**
   * Define the smarty template used to layout the search form and results listings.
   *
   * @return string
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Custom/KamSearch.tpl';
  }

  /**
   * Construct the search query.
   *
   * @param int $offset
   * @param int $rowcount
   * @param string|object $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   *
   * @return string
   */
   public function all(
     $offset = 0, $rowcount = 0, $sort = NULL,
     $includeContactIDs = FALSE, $justIDs = FALSE
   ) {
     if ($justIDs) {
       $select = "c.id as contact_id";
     }
     else {
       $select = "DISTINCT c.id as contact_id, c.sort_name as sort_name, e.email as email, CONCAT_WS('; ',a.city ,a.street_address) as address, co.name as country, ph.phone as phone";
     }
   
     $this->_from = $this->from();
   
     $where = $this->where($includeContactIDs);
   
     // Define GROUP BY here if needed.
     $grouping = "c.id, c.sort_name, e.email, CONCAT_WS(' - ',co.name ,pr.name)";
   
     $sql = "
             SELECT $select
             FROM   $this->_from
             WHERE  $where
             ";
             
             //for only contact ids ignore order.
             if (!$justIDs) {
               // Define ORDER BY for query in $sort, with default value
               if (!empty($sort)) {
                 if (is_string($sort)) {
                   $sort = CRM_Utils_Type::escape($sort, 'String');
                   $sql .= " ORDER BY $sort ";
                 }
                 else {
                   $sql .= " ORDER BY " . trim($sort->orderBy());
                 }
               }
               else {
                 $sql .= " ORDER BY sort_name ASC";
               }
             }
   
   
             if ($rowcount > 0 && $offset >= 0) {
               $offset = CRM_Utils_Type::escape($offset, 'Int');
               $rowcount = CRM_Utils_Type::escape($rowcount, 'Int');
               $sql .= " LIMIT $offset, $rowcount ";
             }
   
     /* Uncomment the next 2 lines to see the exact query you're generating */
   
     // CRM_Core_Error::debug('sql',$sql);
     // exit();
   
     return $sql;
   }

  /**
   * @return string
   */
   public function from() {
     return "civicrm_contact c
             left join civicrm_email e ON c.id = e.contact_id and e.is_primary=1
             left join civicrm_address a on c.id = a.contact_id and a.is_primary=1
             left join civicrm_country co on a.country_id=co.id
             left join civicrm_phone ph on ph.contact_id=c.id and ph.is_primary=1";
   }

  /**
   * WHERE clause is an array built from any required JOINS plus conditional filters based on search criteria field values.
   *
   * @param bool $includeContactIDs
   *
   * @return string
   */
   public function where($includeContactIDs = FALSE) {
     $clauses = array();
 
     $name = $this->_formValues['name'];
     $type = $this->_formValues['type'];
     $group = $this->_formValues['group'];
     
     $country = $this->_formValues['country'];
     $kraj = $this->_formValues['kraj'];
     $address_name = $this->_formValues['address_name'];
          
     $kam_region = $this->_formValues['kam_region'];
     $denomination = $this->_formValues['denomination'];
     $relationship = $this->_formValues['relationship'];
     $event = $this->_formValues['event'];
     
     $check_email = $this->_formValues['check_email'];
     $check_phone = $this->_formValues['check_phone'];
     $check_country = $this->_formValues['check_country'];
     $check_address = $this->_formValues['check_address'];
     
     if($check_email!=1) {
       unset($this->_columns['Email']);
     }
     
     if($check_phone!=1) {
       unset($this->_columns['Telefon']);
     }
     
     if($check_country!=1) {
       unset($this->_columns['Země']);
     }
     
     if($check_address!=1) {
       unset($this->_columns['Adresa']);
     }
     
     
 
     // name
     $names=explode(" ", $name);
     if(count($names) == 2) {
       $clauses[] = "(c.sort_name LIKE '%{$name}%' OR c.display_name LIKE '%{$name}%' OR e.email LIKE '%{$name}%'
       OR (c.first_name LIKE '%{$names[0]}%' AND c.last_name LIKE '%{$names[1]}%')
       OR (c.first_name LIKE '%{$names[1]}%' AND c.last_name LIKE '%{$names[0]}%'))";
     } else {
       $clauses[] = "(c.sort_name LIKE '%{$name}%' OR c.display_name LIKE '%{$name}%' OR e.email LIKE '%{$name}%')";
     }
 
     // contact type
     if(count($type) > 0) {
       $pom=[];
       foreach ($type as $value) {
         if(strpos($value, '%typ%') !== false) {
           $pomname=explode('%typ%', $value);
           $pom[] .= "c.contact_type = '{$pomname[1]}'";
         } elseif(strpos($value, '%subtyp%') !== false) {
           $pomname=explode('%subtyp%', $value);           
           $pom[] .= "c.contact_sub_type LIKE '%{$pomname[1]}%'";
         }       
       }
       $pom2=implode(' OR ', $pom);
       $clauses[]="(".$pom2.")";
     }
 
     // group
     if(count($group) > 0) {
       $ssGroup = new CRM_Contact_DAO_Group();
       $pom=[];
       foreach ($group as $g) {
         $ssGroup->id = $g;
         if (!$ssGroup->find(TRUE)) {
           CRM_Core_Error::fatal();
         }
         // load smart group IMPORTANT
         CRM_Contact_BAO_GroupContactCache::load($ssGroup);
         
         $result = civicrm_api3('Group', 'getsingle', [
           'return' => ["where_clause"],
           'id' => $g,
         ]);
         $pom[]="(c.id in (select contact_id
                     from civicrm_group_contact
                     where group_id = {$g} and status = 'Added')
                     OR
                     c.id in (select contact_id
                     from civicrm_group_contact_cache
                     where group_id = {$g}))";
       }
       if($groupRadio==1){
         $pom2=implode(' AND ', $pom);
         $clauses[]=$pom2;
       } elseif($groupRadio==0){
         $pom2=implode(' OR ', $pom);
         $clauses[]="(".$pom2.")";
       }
     }
 
     // address
     if($country != "") {
       $clauses[]="a.country_id = {$country}";
     }
     
     // kraj
     if(count($kraj) > 0) {
       $pom=[];
       foreach ($kraj as $value) {
         $pom[] = "a.state_province_id = {$value}";
       }
       $pom2=implode(' OR ', $pom);
       $clauses[]="(".$pom2.")";
     }
 
     // address name
     if($address_name != "") {
       $addresses=explode(" ", $address_name);
       foreach ($addresses as $address) {
         $clauses[] = "(a.street_address LIKE '%{$address}%' OR a.street_number LIKE '%{$address}%' OR  a.street_name LIKE '%{$address}%' OR  a.city LIKE '%{$address}%')";
       }
     }
 
     // kam region
     if(count($kam_region) > 0) {
       $this->_from .= " left join civicrm_value_bank_account_21 du on c.id = du.entity_id";
       $pom=[];
       foreach ($kam_region as $value) {
         $pom[] = "du.region_kam_195 = {$value}";
       }
       $pom2=implode(' OR ', $pom);
       $clauses[]="(".$pom2.")";
     }
 
 
     // denomination
     if(count($denomination) > 0) {
       $this->_from .= " left join civicrm_value_church_custom_field_set_6 de on c.id = de.entity_id";
       $pom=[];
       foreach ($denomination as $value) {
         $pom[] = "de.denomination_10 = '{$value}'";
       }
       $pom2=implode(' OR ', $pom);
       $clauses[]="(".$pom2.")";
     }
 
     /*
     // programy kam
     if($program != "") {
       $clauses[] = "de.program_kam_160 like '%{$program}%'";
     }
     */
     // relationship
     if(count($relationship) > 0) {
       $this->_from .= " left join civicrm_relationship r1 on c.id = r1.contact_id_a
                         left join civicrm_relationship r2 on c.id = r2.contact_id_b";
       $date=date("Y-m-d");
       $pom=[];
       foreach ($relationship as $value) {
         $pom[] = "(
           (r1.relationship_type_id = {$value} AND
             (r1.end_date is null OR r1.end_date <= '{$date}') AND
             (r1.start_date is null OR r1.start_date >= '{$date}'))
           OR ((r2.relationship_type_id = {$value}) AND
             (r2.end_date is null OR r2.end_date <= '{$date}') AND
             (r2.start_date is null OR r2.start_date >= '{$date}'))
         )";
       }
       $pom2=implode(' OR ', $pom);
       $clauses[]="(".$pom2.")";
     }
 
     // event
     if(count($event) > 0) {
       $this->_from .= " left join civicrm_participant p on c.id = p.contact_id
                         left join civicrm_event ev on p.event_id = ev.id";   
       $pom=[];         
       foreach ($event as $value) {
         $pom[] = "ev.id = {$value} AND p.status_id=2";
       }
       $pom2=implode(' OR ', $pom);
       $clauses[]="(".$pom2.")";
     }
     
     $clauses[] = "c.is_deleted = 0";
 
 
     // This if-structure was copied from another search.
     // Not sure what it is supposed to do.
     if ($includeContactIDs) {
       $contactIDs = [];
       foreach ($this->_formValues as $id => $value) {
         if ($value &&
           substr($id, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX
         ) {
           $contactIDs[] = substr($id, CRM_Core_Form::CB_PREFIX_LEN);
         }
       }
 
       if (!empty($contactIDs)) {
         $contactIDs = implode(', ', $contactIDs);
         $clauses[] = "contact_a.id IN ( $contactIDs )";
       }
     }  
 
     return implode(' AND ', $clauses);
   }

  /*
   * Functions below generally don't need to be modified
   */

  /**
   * @inheritDoc
   */
  public function count() {
    $sql = $this->all();

    $dao = CRM_Core_DAO::executeQuery($sql);
    return $dao->N;
  }

  /**
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $returnSQL Not used; included for consistency with parent; SQL is always returned
   *
   * @return string
   */
  public function contactIDs($offset = 0, $rowcount = 0, $sort = NULL, $returnSQL = TRUE) {
    return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
  }

  /**
   * @return array
   */
  public function &columns() {
    return $this->_columns;
  }

  /**
   * @param $title
   */
  public function setTitle($title) {
    if ($title) {
      CRM_Utils_System::setTitle($title);
    }
    else {
      CRM_Utils_System::setTitle(ts('Search'));
    }
  }

  /**
   * @return null
   */
  public function summary() {
    return NULL;
  }
}



