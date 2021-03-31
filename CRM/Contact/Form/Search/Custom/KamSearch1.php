<?php

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Contact_Form_Search_Custom_KamSearch extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
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
      ts('Name') => 'sort_name',
      ts('Email') => 'email',
      ts('Address') => 'address',
    );
  }



  public function GetRelationship() {
    $result = civicrm_api3('RelationshipType', 'get', [
      'sequential' => 1,
      'return' => ["label_a_b","label_b_a"],
      'is_active' => 1,
      'options' => ['limit' => 0, 'sort' => "label_a_b", "is_active" => 1],
    ]);

    $relationship = ['' => ts('- libovolný vztah -')];
    foreach ($result["values"] as $value) {
        $relationship+=[$value["id"] => $value["label_a_b"]." / ".$value["label_b_a"]];
    }

    return $relationship;
  }

  public function GetEvent() {
    $result = civicrm_api3('Event', 'get', [
      'sequential' => 1,
      'return' => ["event_title"],
      'is_active' => 1,
      'options' => ['limit' => 0, 'sort' => "event_title", "is_active" => 1],
    ]);

    $event = ['' => ts('- libovolná akce -')];
    foreach ($result["values"] as $value) {
        $event+=[$value["id"] => $value["event_title"]];
    }

    return $event;
  }

  public function GetContactType() {
    $result_type = civicrm_api3('ContactType', 'get', [
      'sequential' => 1,
      'return' => ["label", "name"],
      'is_active' => 1,
      'parent_id' => ['IS NULL' => 1],
      'options' => ['limit' => 0, 'sort' => "id", "is_active" => 1],
    ]);

    $result_subtype = civicrm_api3('ContactType', 'get', [
      'sequential' => 1,
      'return' => ["label", "parent_id", "name"],
      'is_active' => 1,
      'parent_id' => ['IS NOT NULL' => 1],
      'options' => ['limit' => 0, 'sort' => "id", "is_active" => 1],
    ]);

    $return = ['' => ts('- libovolný typ -')];
    foreach ($result_type["values"] as $type) {
        $return+=[$type["name"] => $type["label"]];
        foreach ($result_subtype["values"] as $subtype) {
          if($type["id"] == $subtype["parent_id"]) {
            $return+=[$type["name"]." / ".$subtype["name"] => str_repeat('&nbsp;', 5).$subtype["label"]];
          }
        }
    }

    return $return;
  }
  
  public function GetOptionValue() {
    $result = civicrm_api3('OptionValue', 'get', [
    'sequential' => 1,
    'return' => ["label", "value", "option_group_id"],
    'option_group_id' => ["IN" => ["region_20180617055921", "denominace_20170221220023"]],
    'options' => ['limit' => 0, 'sort' => "label", "is_active" => 1],
  ]);

    $region = ['' => ts('- libovolný region -')];
    $denominace = ['' => ts('- libovolná denominace -')];
    foreach ($result["values"] as $value) {
        if($value["option_group_id"]=="146") {
          $region+=[$value["value"] => $value["label"]];
        } else {
          $denominace+=[$value["value"] => $value["label"]];
        }
    }
    $return = ["region"=>$region, "denominace"=>$denominace];
    return $return;
  }

  public function GetRegionKam() {
    $result = civicrm_api3('OptionValue', 'get', [
    'sequential' => 1,
    'return' => ["label", "value"],
    'option_group_id' => "region_20180617055921",
    'options' => ['limit' => 0, 'sort' => "label", "is_active" => 1],
  ]);

    $type = ['' => ts('- libovolný region -')];
    foreach ($result["values"] as $value) {
        $type+=[$value["value"] => $value["label"]];
    }

    return $type;
  }

  public function GetDenomination() {
    $result = civicrm_api3('OptionValue', 'get', [
    'sequential' => 1,
    'return' => ["label", "value"],
    'option_group_id' => "denominace_20170221220023",
    'options' => ['limit' => 0, 'sort' => "label", "is_active" => 1],
  ]);

    $type = ['' => ts('- libovolná denominace -')];
    foreach ($result["values"] as $value) {
        $type+=[$value["value"] => $value["value"]];
    }

    return $type;
  }

  public function GetKamProgram() {
    $result = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'return' => ["label", "value"],
      'option_group_id' => "program_kam_20180921231550",
      'options' => ['limit' => 0, 'sort' => "label", "is_active" => 1],
    ]);

    $type = ['' => ts('- libovolný program -')];
    foreach ($result["values"] as $value) {
        $type+=[$value["value"] => $value["label"]];
    }

    return $type;
  }

  public function GetCountry() {
    $result = civicrm_api3('Country', 'get', [
      'sequential' => 1,
      'return' => ["name"],
      'options' => ['limit' => 0, 'sort' => "name", "is_active" => 1],
    ]);

    $country = ['' => ts('- libovolný stát -')];
    foreach ($result["values"] as $value) {
        $country+=[$value["id"] => $value["name"]];
    }

    return $country;
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
    $form->addElement('select', 'type', ts('Druh kontaktu'), $this->GetContactType(), ['class' => 'crm-select2 huge']);
    $form->addElement('select', 'group', ts('Je ve skupině'), $group, ['class' => 'crm-select2 huge','multiple' => 'multiple','placeholder' => '- je ve skupině -']);
    $form->addElement('select', 'country', ts('Stát'), $this->GetCountry(), ['class' => 'crm-select2 huge', 'onChange'=>'CountryChange(this.value)']);
    $form->addElement('select', 'kraj', ts('Kraj'), [], ['class' => 'crm-select2 huge','multiple' => 'multiple','placeholder' => '- zvolte první stát -', 'disabled']);
    $form->addElement('text', 'address_name', ts('Název adresy'));
    $form->addElement('select', 'kam_region', ts('KAM region'), $this->GetRegionKam(), ['class' => 'crm-select2 huge']);
    $form->addElement('select', 'denomination', ts('Církev/Denominace'), $this->GetDenomination(), ['class' => 'crm-select2 huge']);
    //$form->addElement('select', 'program', ts('Programy KAM'), $this->GetKamProgram(), ['class' => 'crm-select2 huge']);
    $form->addElement('select', 'relationship', ts('Má vztah'), $this->GetRelationship(), ['class' => 'crm-select2 huge']);
    $form->addElement('select', 'event', ts('Účastník události'), $this->GetEvent(), ['class' => 'crm-select2 huge']);
    $form->addElement('hidden', 'id');

    $this->setTitle('Vyhledávání');

    $form->assign('elements', array('name', 'type', 'group', 'country', 'kraj', 'town', 'address_name', 'kam_region', 'denomination', 'program', 'relationship', 'event', 'id'));
  }

  /**
   * Define the smarty template used to layout the search form and results
   * listings.
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Custom/KamSearch.tpl';
  }

  /**
   * Construct the search query.
   */
  public function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    if ($justIDs) {
      $select = "c.id as contact_id";
    }
    else {
      $select = "DISTINCT c.id as contact_id, c.sort_name as sort_name, e.email as email, CONCAT_WS('; ',a.city ,a.street_address) as address, c.id as id2";
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
      $sql .= "ORDER BY sort_name asc";
    }

    $sql .= " LIMIT ";
    $sql .= $this->_limit;

    /* Uncomment the next 2 lines to see the exact query you're generating */

    CRM_Core_Error::debug('sql',$sql);
    exit();

    return $sql;
  }

  /**
   * Returns the join of tables on which to query.
   */
  public function from() {
    return "civicrm_contact c
            left join civicrm_email e ON c.id = e.contact_id and e.is_primary=1
            left join civicrm_address a on c.id = a.contact_id and a.is_primary=1";
  }

  /**
   * Returns the where clause.
   */
  public function where($includeContactIDs = FALSE) {
    $clauses = array();

    $name = $this->_formValues['name'];
    $group = $this->_formValues['group'];
    $relationship = $this->_formValues['relationship'];
    $event = $this->_formValues['event'];
    $kam_region = $this->_formValues['kam_region'];
    $address_name = $this->_formValues['address_name'];
    $country = $this->_formValues['country'];
    $type = $this->_formValues['type'];
    $denomination = $this->_formValues['denomination'];
    $program = $this->_formValues['program'];
    $limit = $this->_formValues['limit'];

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
    if($type != "") {
      if(strpos($type, ' / ') !== false) {
          $types=explode(' / ', $type);
          $clauses[] = "c.contact_type = '{$types[0]}' AND contact_sub_type LIKE '%{$types[1]}%'";
      } else {
          $clauses[] = "c.contact_type = '{$type}'";
      }
    }

    // group
    if($group != "") {
      foreach ($group as $g) {
        $clauses[]="c.id in (select contact_id
                    from civicrm_group_contact
                    where group_id = {$g})";
      }
    }

    // address
    if($country != "") {
      $clauses[] = "a.country_id = {$country}";
    }

    if($address_name != "") {
      $addresses=explode(" ", $address_name);
      foreach ($addresses as $address) {
        $clauses[] = "(a.street_address LIKE '%{$address}%' OR a.street_number LIKE '%{$address}%' OR  a.street_name LIKE '%{$address}%' OR  a.city LIKE '%{$address}%')";
      }
    }

    // kam region
    if($kam_region != "") {
      $this->_from .= " left join civicrm_value_bank_account_21 du on c.id = du.entity_id";
      $clauses[] = "du.region_kam_195 = {$kam_region}";
    }

    if($denomination != "" || $program != "") {
      $this->_from .= " left join civicrm_value_church_custom_field_set_6 de on c.id = de.entity_id";
    }

    // denomination
    if($denomination != "") {
      $clauses[] = "de.denomination_10 = '{$denomination}'";
    }

    /*
    // programy kam
    if($program != "") {
      $clauses[] = "de.program_kam_160 like '%{$program}%'";
    }
    */
    // relationship
    if($relationship != "") {
      $this->_from .= " left join civicrm_relationship r1 on c.id = r1.contact_id_a
                        left join civicrm_relationship r2 on c.id = r2.contact_id_b";
      $date=date("Y-m-d");
      $clauses[] = "(
        (r1.relationship_type_id = {$relationship} AND
          (r1.end_date is null OR r1.end_date <= '{$date}') AND
          (r1.start_date is null OR r1.start_date >= '{$date}'))
        OR ((r2.relationship_type_id = {$relationship}) AND
          (r2.end_date is null OR r2.end_date <= '{$date}') AND
          (r2.start_date is null OR r2.start_date >= '{$date}'))
      )";
    }

    // event
    if($event != "") {
      $this->_from .= " left join civicrm_participant p on c.id = p.contact_id
                        left join civicrm_event ev on p.event_id = ev.id";
      $clauses[] = "ev.id = {$event} AND p.status_id=2";
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
        $clauses[] = "contact.id IN ( $contactIDs )";
      }
    }
    $clauses[] = "c.is_deleted = 0";

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
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script>
function CountryChange(value) {
  if(value!="") {
    var o;
    CRM.api3('StateProvince', 'get', {
      "sequential": 1,
      "return": ["name"],
      "country_id": value,
      "options": {"limit":0}
    }).then(function(result) {
      $("#s2id_kraj .select2-search-choice").remove();
      $("#kraj").find('option').remove();
      $("#kraj").attr('placeholder', '- libovolný kraj -');
      $("#kraj").attr("disabled", false);
      for (var i = 0; i < result["values"].length; i++) {
        o=new Option(result["values"][i]["name"], result["values"][i]["id"]);
        $(o).html(result["values"][i]["name"]);
        $("#kraj").append(o);

      }
    }, function(error) {
      // oops
    });
  } else {
    //$("#kraj option:selected").prop("selected", false);
    $("#s2id_kraj .select2-search-choice").remove();
    $("#kraj").find('option').remove();
    $("#kraj").attr("disabled", true);
  }

}
</script>
<?php
