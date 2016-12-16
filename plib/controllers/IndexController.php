<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.

class IndexController extends pm_Controller_Action
{
    protected $_accessLevel = 'admin';

    public function init()
    {
        parent::init();

        // Init title for all actions
        $this->view->pageTitle = $this->lmsg('page_title');
    }

    public function indexAction()
    {
        // Default action is formAction
        $this->_forward('form');
    }

    /**
     * Default action which creates the form in the settings and processes the requests
     */
    public function formAction()
    {
        // Set the description text
        $this->view->output_description = $this->addSpanTranslation('output_description', 'description-extension');

        // Init form here
        $form = new pm_Form_Simple();
        $form->addElement('text', 'license_key', ['label' => $this->lmsg('form_license_key'), 'value' => pm_Settings::get('license_key'), 'required' => true, 'validators' => [['NotEmpty', true],],]);
        $form->addElement('text', 'server_name', ['label' => $this->lmsg('form_server_name'), 'value' => pm_Settings::get('server_name'), 'required' => true, 'validators' => [['NotEmpty', true],],]);
        $this->installationType('servers', $form);
        $this->installationType('apm', $form);
        $form->addControlButtons(['sendTitle' => $this->lmsg('form_button_send'), 'cancelLink' => pm_Context::getModulesListUrl(),]);

        // Process the form - save the license key and run the installation scripts
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            $this->processPostRequest($form);
        }

        $this->view->form = $form;
    }

    /**
     * Adds a span element with a CSS class to the form field and removes not provided language strings
     *
     * @param string $language_string
     * @param string $class_name
     *
     * @return string
     */
    private function addSpanTranslation($language_string, $class_name)
    {
        $translated_string = $this->lmsg($language_string);

        if ($translated_string == '[['.$language_string.']]') {
            $translated_string = '';
        }

        $span_element = '<span class="'.$class_name.'">'.$translated_string.'</span>';

        return $span_element;
    }

    /**
     * Adds elements to the pm_Form_Simple object depending on installation type
     *
     * @param string         $type
     * @param pm_Form_Simple $form
     */
    private function installationType($type, &$form)
    {
        $installation_done = $this->checkInstallationState($type);

        if ($type == 'servers') {
            $form->addElement('description', 'type_servers_logo', ['description' => $this->addSpanTranslation('form_type_servers_logo', 'logo-product-servers'), 'escape' => false]);
            if ($installation_done == false) {
                $form->addElement('description', 'servers_install', ['description' => $this->addSpanTranslation('form_type_servers_install', 'product-installed-servers'), 'escape' => false]);
                $form->addElement('checkbox', 'servers', ['label' => $this->lmsg('form_type_servers'), 'value' => pm_Settings::get('servers'), 'checked' => true]);
            } else {
                $form->addElement('description', 'servers_installed', ['description' => $this->addSpanTranslation('form_type_servers_installed', 'product-installed-servers'), 'escape' => false]);
                $form->addElement('checkbox', 'servers', ['label' => $this->lmsg('form_type_servers_reinstall'), 'value' => '', 'checked' => false]);
            }

            $form->addElement('description', 'type_servers_description', ['description' => $this->addSpanTranslation('form_type_servers_description', 'description-product'), 'escape' => false]);

            return;
        }

        if ($type == 'apm') {
            $form->addElement('description', 'type_apm_logo', ['description' => $this->addSpanTranslation('form_type_apm_logo', 'logo-product-apm'), 'escape' => false]);

            if ($installation_done == false) {
                $form->addElement('description', 'apm_install', ['description' => $this->addSpanTranslation('form_type_apm_install', 'product-installed-apm'), 'escape' => false]);
                $form->addElement('checkbox', 'apm', ['label' => $this->lmsg('form_type_apm'), 'value' => pm_Settings::get('apm'), 'checked' => true]);
            } else {
                $form->addElement('description', 'apm_installed', ['description' => $this->addSpanTranslation('form_type_apm_installed', 'product-installed-apm'), 'escape' => false]);
                $form->addElement('checkbox', 'apm', ['label' => $this->lmsg('form_type_apm_reinstall'), 'value' => '', 'checked' => false]);
            }

            $php_versions = $this->getPleskPhpVersions();

            if (!empty($php_versions)) {
                $form->addElement('description', 'type_apm_php_versions', ['description' => $this->addSpanTranslation('form_type_apm_php_versions', 'description-php-versions'), 'escape' => false]);
                foreach ($php_versions as $php_version => $php_bin_path) {
                    if ($this->checkInstallationState('php_versions_'.str_replace('.', '', $php_version))) {
                        $form->addElement('checkbox', 'php_versions_'.$php_version, ['label' => $php_version.' ['.$this->lmsg('form_type_apm_php_activated').']', 'value' => '', 'checked' => false]);

                        continue;
                    }

                    $form->addElement('checkbox', 'php_versions_'.$php_version, ['label' => $php_version, 'value' => '', 'checked' => true]);
                }
            }

            $form->addElement('description', 'type_apm_description', ['description' => $this->addSpanTranslation('form_type_apm_description', 'description-product'), 'escape' => false]);
        }
    }

    /**
     * Checks state of the transferred option name which defines the state of the installation
     *
     * @param string $type
     *
     * @return null|string
     */
    private function checkInstallationState($type)
    {
        return pm_Settings::get($type);
    }

    /**
     * Gets all installed Plesk PHP versions with the help of shell script
     *
     * @return array
     */
    private function getPleskPhpVersions()
    {
        $php_versions = array();

        $result = pm_ApiCli::callSbin('phpversions.sh', array(), pm_ApiCli::RESULT_FULL);

        if (empty($result['code'] AND !empty($result['stdout']))) {
            $php_versions_object = json_decode($result['stdout']);

            foreach ($php_versions_object as $php_version) {
                if (!array_key_exists($php_version->fullVersion, $php_versions)) {
                    if ($php_version->status == 'enabled') {
                        $php_versions[$php_version->fullVersion] = substr($php_version->clipath, 0, strrpos($php_version->clipath, '/'));
                    }
                }
            }
        }

        return $php_versions;
    }

    /**
     * Processes POST request - after form submission
     *
     * @param $form
     */
    private function processPostRequest($form)
    {
        $license_key = $form->getValue('license_key');

        if ($this->validateLicenseKey($license_key)) {
            pm_Settings::set('license_key', $license_key);

            $server_name = $form->getValue('server_name');
            pm_Settings::set('server_name', $server_name);

            $this->_status->addMessage('info', $this->lmsg('message_success'));

            if ($form->getValue('servers')) {
                if ($this->runInstallation('servers', $license_key, $server_name)) {
                    pm_Settings::set('servers', $form->getValue('servers'));

                    $this->_status->addMessage('info', $this->lmsg('message_success_servers'));
                }
            }

            if ($form->getValue('apm')) {
                $php_versions = $this->getSelectedPleskPhpVersion();

                if (empty($php_versions)) {
                    $this->_status->addMessage('warning', $this->lmsg('message_warning_php_version'));
                } elseif ($this->runInstallation('apm', $license_key, $server_name, $php_versions)) {
                    pm_Settings::set('apm', $form->getValue('apm'));

                    $this->_status->addMessage('info', $this->lmsg('message_success_apm'));
                }
            }


        }

        $this->_helper->json(['redirect' => pm_Context::getBaseUrl()]);
    }

    /**
     * Validates the New Relic license key
     *
     * @param string $license_key
     *
     * @return bool
     */
    private function validateLicenseKey($license_key)
    {
        if (empty($license_key) OR strlen($license_key) != 40) {
            $this->_status->addMessage('error', $this->lmsg('message_error_key'));

            return false;
        }

        return true;
    }

    /**
     * Starts the installation process of the service using shell scripts
     *
     * @param string $type
     * @param string $license_key
     * @param string $server_name
     *
     * @return bool
     * @throws pm_Exception
     */
    private function runInstallation($type, $license_key, $server_name = '', $php_versions = '')
    {
        $options = array();

        $options[] = $license_key;
        $options[] = addslashes($server_name);
        $options[] = $php_versions;

        $result = pm_ApiCli::callSbin($type.'.sh', $options, pm_ApiCli::RESULT_FULL);

        if ($result['code']) {
            throw new pm_Exception("{$result['stdout']}\n{$result['stderr']}");
        }

        return true;
    }

    /**
     * Gets all selected Plesk PHP version for the APM service
     *
     * @return string
     */
    private function getSelectedPleskPhpVersion()
    {
        $php_versions_selected = '';
        $php_versions_selected_array = array();
        $php_versions_installed = $this->getPleskPhpVersions();

        foreach ($php_versions_installed as $php_version_installed => $php_bin_path_installed) {
            if ($this->getRequest()->get('php_versions_'.str_replace('.', '', $php_version_installed))) {
                $php_versions_selected_array[] = $php_bin_path_installed;
                pm_Settings::set('php_versions_'.str_replace('.', '', $php_version_installed), true);
            }
        }

        if (!empty($php_versions_selected_array)) {
            $php_versions_selected = implode(':', $php_versions_selected_array);
        }

        return $php_versions_selected;
    }
}
