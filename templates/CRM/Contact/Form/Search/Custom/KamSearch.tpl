{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<style>
{literal}
.inline {
  display: inline-block !important;
}

.custom-label {
  width: 150px !important;
  text-align: right !important;
  margin-right: 5px;
}

.custom_section {
  padding: 15px;
  padding-bottom: 30px;
}

.custom-row {
  width: 400px !important;
  margin-bottom: 5px;
}

.custom_section input {
  width: 200px !important;
}

.select2-container {
  width: 222px !important;
}

.custom_hidden .custom-row {
  width: 100px !important;
}

.custom_hidden .custom-label {
  width: 70px !important;
}
{/literal}
</style>
<!-- SEARCH -->
<div class="crm-block crm-form-block crm-contact-custom-search-form-block">
<div class="crm-accordion-wrapper crm-custom_search_form-accordion {if $rows}collapsed{/if}">
    <div class="crm-accordion-header crm-master-accordion-header">
      {ts}Edit Search Criteria{/ts}
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
        <table class="form-layout-compressed">
            {* Loop through all defined search criteria fields (defined in the buildForm() function). *}
            <div class="custom_section">
            {foreach from=$elements item=element}
                {if $element=="group" || $element=="address_name"}
                  <div class="crm-contact-custom-search-form-row-{$element} inline custom-row">
                        <div class="custom-label inline">{$form.$element.label}</div>
                        <div class="inline">{$form.$element.html}</div>
                  </div>
                </div>
                <div class="custom_section">
                {elseif $element=="event"}
                  <div class="crm-contact-custom-search-form-row-{$element} inline custom-row">
                        <div class="custom-label inline">{$form.$element.label}</div>
                        <div class="inline">{$form.$element.html}</div>
                  </div>
                </div>
                <div class="custom_hidden">
                  <h3>Sloupce zobrazené ve vyhledávání</h3>
                {else}
                <div class="crm-contact-custom-search-form-row-{$element} inline custom-row">
                      <div class="custom-label inline">{$form.$element.label}</div>
                      <div class="inline">{$form.$element.html}</div>
                </div>
                {/if}            
            {/foreach}
            </div>
        </table>
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
    </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
</div><!-- /.crm-form-block -->
<!-- RESULTS -->

{if $rowsEmpty || $rows}
<div class="crm-content-block">
{if $rowsEmpty}
    {include file="CRM/Contact/Form/Search/Custom/EmptyResults.tpl"}
{/if}

{if $summary}
    {$summary.summary}: {$summary.total}
{/if}

{if $rows}
  <div class="crm-results-block">
    {* Search request has returned 1 or more matching rows. Display results and collapse the search criteria fieldset. *}
        {* This section handles form elements for action task select and submit *}
       <div class="crm-search-tasks">
        {include file="CRM/Contact/Form/Search/ResultTasks.tpl"}
    </div>
        {* This section displays the rows along and includes the paging controls *}
      <div class="crm-search-results">

        {include file="CRM/common/pager.tpl" location="top"}

        {* Include alpha pager if defined. *}
        {if $atoZ}
            {include file="CRM/common/pagerAToZ.tpl"}
        {/if}

        {strip}
        <a href="#" class="crm-selection-reset crm-hover-button"><i class="crm-i fa-times-circle-o" aria-hidden="true"></i> {ts}Reset all selections{/ts}</a>
        <table class="selector row-highlight" summary="{ts}Search results listings.{/ts}">
            <thead class="sticky">
                <tr>
                <th scope="col" title="Select All Rows">{$form.toggleSelect.html}</th>
                {foreach from=$columnHeaders item=header}
                    <th scope="col">
                        {if $header.sort}
                            {assign var='key' value=$header.sort}
                            {$sort->_response.$key.link}
                        {else}
                            {$header.name}
                        {/if}
                    </th>
                {/foreach}
                <th>&nbsp;</th>
                </tr>
            </thead>
            {counter start=0 skip=1 print=false}
            {foreach from=$rows item=row}
                <tr id='rowid{$row.contact_id}' class="{cycle values="odd-row,even-row"}">
                    {assign var=cbName value=$row.checkbox}
                    <td>{$form.$cbName.html}</td>
                    {foreach from=$columnHeaders item=header}
                        {assign var=fName value=$header.sort}
                        {if $fName eq 'sort_name'}
                            <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`&key=`$qfKey`"}">{$row.sort_name}</a></td>
                        {else}
                            <td>{$row.$fName}</td>
                        {/if}
                    {/foreach}
                    <td>{$row.action}</td>
                </tr>
            {/foreach}
        </table>
        {/strip}
        {include file="CRM/common/pager.tpl" location="bottom"}
        </p>
    {* END Actions/Results section *}
    </div>
    </div>  
<script>
{literal}
CRM.$(".crm-ajax-selection-form").removeClass("crm-ajax-selection-form");
{/literal}
</script>
{/if}
</div>
{/if}

<script>
{literal}
function CountryChange(value) {
  CRM.$("#s2id_custom-loading-3").find('[id^="s2id_autogen"]').val('loading...');
  if(value!="") {
    var o;
    CRM.api3('StateProvince', 'get', {
      "sequential": 1,
      "return": ["name"],
      "country_id": value,
      "options": {"limit":0}
    }).then(function(result) {
      CRM.$("#s2id_kraj .select2-search-choice").remove();
      CRM.$("#custom-loading-3").find('option').remove();    
      for (var i = 0; i < result["values"].length; i++) {
        o=new Option(result["values"][i]["name"], result["values"][i]["id"]);
        CRM.$(o).html(result["values"][i]["name"]);
        CRM.$("#custom-loading-3").append(o);
      }
      CRM.$("#s2id_custom-loading-3").find('[id^="s2id_autogen"]').val('- libovolný kraj -');
      CRM.$("#custom-loading-3").attr('placeholder', '- libovolný kraj -');
      CRM.$("#custom-loading-3").attr("disabled", false);
    }, function(error) {
      // oops
    });
  } else {
    //$("#kraj option:selected").prop("selected", false);
    CRM.$("#s2id_custom-loading-3").find('[id^="s2id_autogen"]').val('- zvolte první zemi -');
    CRM.$("#s2id_kraj .select2-search-choice").remove();
    CRM.$("#custom-loading-3").find('option').remove();
    CRM.$("#custom-loading-3").attr("disabled", true);
  }

}

CRM.$(function($) {
  CRM.api3('ContactType', 'get', {
    "sequential": 1,
    "return": ["label", "name", "parent_id"],
    'is_active': 1,
    "options": {"limit":0, "sort":"parent_id, id"}
  }).then(function(result) {
    for (var i = 0; i < result["values"].length; i++) {
      if(result["values"][i]["parent_id"]==null) {
        o=new Option(result["values"][i]["label"], result["values"][i]["id"]+"%typ%"+result["values"][i]["name"]);
        CRM.$("#custom-loading-1").append(o);
      } else {
        o=new Option(" - "+result["values"][i]["label"], result["values"][i]["id"]+"%subtyp%"+result["values"][i]["name"]);
        CRM.$("#custom-loading-1").find("option[value^='"+result["values"][i]["parent_id"]+"%typ%']").after(o);
      }
    }    
    CRM.$("#s2id_custom-loading-1").find('[id^="s2id_autogen"]').val('- libovolný typ kontaktu -');
    CRM.$("#custom-loading-1").attr('placeholder', '- libovolný typ kontaktu -');
    CRM.$("#custom-loading-1").attr("disabled", false);
  });
  
  CRM.api3('Country', 'get', {
    "sequential": 1,
    "return": ["name"],
    'is_active': 1,
    "options": {"limit":0, "sort":"name"}
  }).then(function(result) {  
    for (var i = 0; i < result["values"].length; i++) {
      o=new Option(result["values"][i]["name"], result["values"][i]["id"]);
      CRM.$("#custom-loading-2").append(o);    
    }  
    CRM.$("#s2id_custom-loading-2").find('[id^="select2-chosen"]').html(' - libovolná země - '); 
    CRM.$("#custom-loading-2").find("option[value='']").val(" - libovolná země - ");
    CRM.$("#custom-loading-2").attr("disabled", false); 
  });
  
  CRM.api3('OptionValue', 'get', {
    "sequential": 1,
    "return": ["label", "value", "option_group_id"],
    'is_active': 1,
    'option_group_id': {"IN":["region_20180617055921", "denominace_20170221220023"]},
    "options": {"limit":0, "sort":"label"}
  }).then(function(result) {  
    for (var i = 0; i < result["values"].length; i++) {
      o=new Option(result["values"][i]["label"], result["values"][i]["value"]);
      if(result["values"][i]["option_group_id"]=="146") {
        CRM.$("#custom-loading-4").append(o);   
      } else {
        CRM.$("#custom-loading-5").append(o);   
      }     
    }   
    CRM.$("#s2id_custom-loading-4").find('[id^="s2id_autogen"]').val('- libovolný region -');
    CRM.$("#s2id_custom-loading-5").find('[id^="s2id_autogen"]').val('- libovolná denominace -');
    CRM.$("#custom-loading-4").attr('placeholder', '- libovolný region -');
    CRM.$("#custom-loading-5").attr('placeholder', '- libovolná denominace -');
    CRM.$("#custom-loading-4").attr("disabled", false);   
    CRM.$("#custom-loading-5").attr("disabled", false); 
  });
  CRM.api3('RelationshipType', 'get', {
    "sequential": 1,
    "return": ["label_a_b","label_b_a"],
    'is_active': 1,
    "options": {"limit":0, 'sort':"label_a_b"}
  }).then(function(result) {
    for (var i = 0; i < result["values"].length; i++) {
      if(result["values"][i]["label_a_b"] == result["values"][i]["label_b_a"]) {
        o=new Option(result["values"][i]["label_a_b"], result["values"][i]["id"]);
      } else {
        o=new Option(result["values"][i]["label_a_b"]+' <nebo> '+result["values"][i]["label_b_a"], result["values"][i]["id"]);
      }
      CRM.$("#custom-loading-6").append(o);
    }
    CRM.$("#s2id_custom-loading-6").find('[id^="s2id_autogen"]').val('- libovolný vztah -');
    CRM.$("#custom-loading-6").attr('placeholder', '- libovolný vztah -');
    CRM.$("#custom-loading-6").attr("disabled", false);
  });
  CRM.api3('Event', 'get', {
    "sequential": 1,
    "return": ["event_title"],
    'is_active': 1,
    "options": {"limit":0, "sort":"event_title"}
  }).then(function(result) { 
    for (var i = 0; i < result["values"].length; i++) {
      o=new Option(result["values"][i]["event_title"], result["values"][i]["id"]);
      CRM.$("#custom-loading-7").append(o);    
    }   
    CRM.$("#s2id_custom-loading-7").find('[id^="s2id_autogen"]').val('- libovolná akce -');
    CRM.$("#custom-loading-7").attr('placeholder', '- libovolná akce -');
    CRM.$("#custom-loading-7").attr("disabled", false); 
  });
});
{/literal}
</script>