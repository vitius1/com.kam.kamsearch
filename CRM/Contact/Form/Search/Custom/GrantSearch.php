<?php

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Contact_Form_Search_Custom_GrantSearch extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  protected $_formValues;
  protected $_aclFrom = NULL;
  protected $_aclWhere = NULL;
  public $_permissionedComponent;
  public $_from;
  public $_limit = 1000;

  public function __construct(&$formValues) {
    $this->_formValues = $formValues;

    // Define the columns for search result rows
    $this->_columns = array(
      ts('Žadatel') => 'sort_name',
      ts('Název') => 'nazev',
      ts('Oddělení') => 'oddeleni',
      ts('Způsob podání žádosti') => 'zpusob',
      ts('Fáze') => 'stav',
      ts('Návrh') => 'castka',
      ts('Žádáno o') => 'castka_schvalena',
      ts('Dárce') => 'darce',
      ts('Termín podání') => 'termin_podani',
      ts('Schváleno?') => 'schvaleno',
      ts('Darováno') => 'castka_vydana',
      ts('Reportováno') => 'datum_reportu',
      ts('Akce') => 'edit',
    );
  }

  /**
   * Build the form.
   *
   * The form consists of an autocomplete field to select an organization.
   */
  public function buildForm(&$form) {
    $result = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'return' => ["label", "value", "option_group_id"],
      'option_group_id' => ['IN' => ["grant_status", "kam_oddeleni_20190213100006"]],
      'is_active' => 1,
      'options' => ['limit' => 0],
    ]);
    
    $faze = [];
    $oddeleni = [];
    foreach ($result["values"] as $value) {
        if($value["option_group_id"] == "186") {
          $oddeleni+=[$value["value"] => $value["label"]];
        } else {
          $faze+=[$value["value"] => $value["label"]];
        }
        
    }
    
    $andOr = [
      '1' => ts('Include'),
      '0' => ts('Exclude')
    ];
    // add select for groups
    $group = CRM_Core_PseudoConstant::nestedGroup();

    $form->addElement('text', 'name', ts('Jméno nebo email žadatele'));
    
    $form->addElement('text', 'nazev', ts('Název grantu'));
    
    $form->addElement('select', 'status', ts('Fáze grantu'), $faze, ['class' => 'crm-select2 huge','multiple' => 'multiple','placeholder' => '- libovolná fáze -']);
    
    $form->addRadio('statusradio', ts(''), $andOr, ['class' => 'crm-form-radio']);
    $form->setDefaults(array('statusradio'=>'1'));    
    
    $form->addElement('select', 'oddeleni', ts('Oddělení'), $oddeleni, ['class' => 'crm-select2 huge','multiple' => 'multiple','placeholder' => '- libovolné oddělení -']);



    $form->addElement('hidden', 'id');

    $this->setTitle('Vyhledávání');

    $form->assign('elements', array('name', 'nazev', 'statusradio', 'status', 'oddeleni', 'id'));
  }

  /**
   * Define the smarty template used to layout the search form and results
   * listings.
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Custom/GrantSearch.tpl';
  }

  /**
   * Construct the search query.
   */
  public function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    if ($justIDs) {
      $select = "g.id as grant_id";
    }
    else {
      $select = "DISTINCT g.id as grant_id, g.contact_id as contact_id, c.sort_name as sort_name, 
      CONCAT('<a href=".'"'."https://crm.kam.cz/civicrm/contact/view/grant?reset=1&action=update&id=', g.id, '&cid=', g.contact_id,'".'"'.">', custom.n_zev_553, '</a>') as nazev, 
      status.label as stav, REPLACE(format(g.amount_total,0), ',', ' ') as castka, REPLACE(format(custom.po_adovan_stka_po_int_schv_len__558,0), ',', ' ') as castka_schvalena, 
      REPLACE(format(g.amount_granted,0), ',', ' ') castka_vydana, oddeleni.label as oddeleni, DATE_FORMAT(grant_report_received, '%d.%m.%Y') as datum_reportu,
      zpusob.label as zpusob,
      case 
        when schv_len_grantu_569=1 then 'Ano'
        when schv_len_grantu_569=0 then 'Ne'
        else ''
      end as 'schvaleno'   
        ,
      darce.sort_name as darce, DATE_FORMAT(custom.term_n_od_d_rce_pro_pod_n_dosti_568, '%d.%m.%Y') as termin_podani, 
      CONCAT('<a class=".'"'."crm-hover-button".'"'." href=".'"'."https://crm.kam.cz/civicrm/contact/view/grant?reset=1&action=update&id=', g.id, '&cid=', g.contact_id,'".'"'.">Upravit</a>',
      '<a class=".'"'."crm-hover-button action-item small-popup".'"'." href=".'"'."https://crm.kam.cz/civicrm/contact/view/grant?reset=1&action=delete&selectedChild=grant&id=', g.id, '&cid=', g.contact_id,'".'"'.">Smazat</a>') as edit";
    }

    $this->_from = $this->from();

    $where = $this->where($includeContactIDs);

    $sql = "
            SELECT $select
            FROM   $this->_from
            WHERE  $where
            ";
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
      $sql .= "ORDER BY status.label asc";
    }

    /* Uncomment the next 2 lines to see the exact query you're generating */

    // CRM_Core_Error::debug('sql',$sql);
    // exit();

    return $sql;
  }

  /**
   * Returns the join of tables on which to query.
   */
  public function from() {
    return "civicrm_grant g
    join civicrm_contact c on g.contact_id=c.id
    left join civicrm_email e ON c.id = e.contact_id and e.is_primary=1
    left join civicrm_value_grants_kam_27 custom on g.id=custom.entity_id
    left join civicrm_option_value status on status.option_group_id=23 and status.value=g.status_id
    left join civicrm_option_value zpusob on zpusob.option_group_id=268 and zpusob.value=custom.typ_grantu_ii__571
    left join civicrm_contact darce on custom.donor_125=darce.id
    left join civicrm_option_value oddeleni on oddeleni.option_group_id=186 and oddeleni.value=custom.odd_len__572";
  }

  /**
   * Returns the where clause.
   */
  public function where($includeContactIDs = FALSE) {
    $clauses = array();
    
    $name = $this->_formValues['name'];
    $status = $this->_formValues['status'];
    $statusradio = $this->_formValues['statusradio'];
    $nazev = $this->_formValues['nazev'];
    $oddeleni = $this->_formValues['oddeleni'];
    $clauses[] = "1=1";
    
    $names=explode(" ", $name);
     if(count($names) == 2) {
       $clauses[] = "(c.sort_name LIKE '%{$name}%' OR c.display_name LIKE '%{$name}%' OR e.email LIKE '%{$name}%'
       OR (c.first_name LIKE '%{$names[0]}%' AND c.last_name LIKE '%{$names[1]}%')
       OR (c.first_name LIKE '%{$names[1]}%' AND c.last_name LIKE '%{$names[0]}%'))";
     } else {
       $clauses[] = "(c.sort_name LIKE '%{$name}%' OR c.display_name LIKE '%{$name}%' OR e.email LIKE '%{$name}%')";
     }
     
     if($status[0] != "") {
       if($statusradio==1) {
         foreach ($status as $value) {
           $pom[]="g.status_id = {$value}";
         }
         $pom2=implode(' OR ', $pom);
         $clauses[]="(".$pom2.")";
       } else {
         foreach ($status as $value) {
           $pom[]="g.status_id != {$value}";
         }
         $pom2=implode(' AND ', $pom);
         $clauses[]="(".$pom2.")";
       }
     }
     
     if($nazev != "") {
       $clauses[] = "custom.n_zev_553 LIKE '%{$nazev}%'";
     }
     
     if($oddeleni != "") {
       foreach ($oddeleni as $value) {
         $pom[]="custom.odd_len__572 = {$value}";
       }
       $pom2=implode(' OR ', $pom);
       $clauses[]="(".$pom2.")";
     }
    
    // This if-structure was copied from another search.
    // Not sure what it is supposed to do.
    if ($includeContactIDs) {
      $contactIDs = array();
      foreach ($this->_formValues as $id => $value) {
        if ($value &&
            substr($id, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX
        ) {
          $contactIDs[] = substr($id, CRM_Core_Form::CB_PREFIX_LEN);
        }
      }

      if (!empty($contactIDs)) {
        $contactIDs = implode(', ', $contactIDs);
        $clauses[] = "grant.id IN ( $contactIDs )";
      }
    }

    

    return implode(' AND ', $clauses);
  }

  /*
   * Functions below generally don't need to be modified
   */

  public function count() {
    $sql = $this->all();

    $dao = CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray
    );
    return $dao->N;
  }

  public function contactIDs($offset = 0, $rowcount = 0, $sort = NULL) {
    return $this->all($offset, $rowcount, $sort);
  }

  public function &columns() {
    return $this->_columns;
  }

  public function setTitle($title) {
    if ($title) {
      CRM_Utils_System::setTitle($title);
    }
    else {
      CRM_Utils_System::setTitle(ts('Search'));
    }
  }

  public function summary() {
    return NULL;
  }

}
