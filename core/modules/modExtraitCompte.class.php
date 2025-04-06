<?php
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
class modExtraitCompte extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;
        $this->numero = 104201;
        $this->rights_class = 'extraitcompte';
        $this->family = "financial";
        $this->module_position = 500;
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Module to generate account extract for a client";
        $this->descriptionlong = "This module allows you to generate an account extract for a client in Dolibarr.";
        $this->editor_name = 'Your Company';
        $this->editor_url = 'http://www.example.com';
        $this->version = '1.0';
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        $this->picto = 'generic';

        $this->dirs = array("/extraitcompte/temp");

        $this->config_page_url = array("extraitcompte.php@extraitcompte");

        $this->langfiles = array("extraitcompte@extraitcompte");

        $this->depends = array();
        $this->requiredby = array();
        $this->conflictwith = array();
        $this->need_dolibarr_version = array(3, 0);
        $this->phpmin = array(5, 2);
        $this->need_javascript_ajax = 0;

        $this->const = array();
        $this->tabs = array();
        $this->dictionaries = array();

        $this->boxes = array();
        $this->cronjobs = array();

        $this->rights = array();
        $this->rights_class = 'extraitcompte';
        $r = 0;

        $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1);
        $this->rights[$r][1] = 'Lire les extraits de compte';
        $this->rights[$r][4] = 'extraitcompte';
        $this->rights[$r][5] = 'read';
        $r++;

        $this->tabs = array();
        $r = 0;

        $this->tabs[$r] = 'thirdparty:+extraitcompte:ExtraitCompte:$user->rights->extraitcompte->read:/custom/extraitcompte/extraitcompte.php?id=__ID__';
    }

    public function init($options = '')
    {
        $sql = array();

        return $this->_init($sql, $options);
    }

    public function remove($options = '')
    {
        $sql = array();

        return $this->_remove($sql, $options);
    }
}
