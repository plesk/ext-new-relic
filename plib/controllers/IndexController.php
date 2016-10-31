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

    public function formAction()
    {
        // Set the description text
        $this->view->output_description = $this->lmsg('output_description');

        // Init form here
        $form = new pm_Form_Simple();
        $form->addElement('text', 'license_key', ['label' => $this->lmsg('form_license_key'), 'value' => pm_Settings::get('license_key'), 'required' => true, 'validators' => [['NotEmpty', true],],]);
        $form->addElement('text', 'server_name', ['label' => $this->lmsg('form_server_name'), 'value' => pm_Settings::get('server_name'), 'required' => true, 'validators' => [['NotEmpty', true],],]);
        $this->installationType('servers', $form);
        $this->installationType('apm', $form);
        $form->addElement('description', 'type_reboot_note', ['description' => $this->lmsg('form_type_reboot_note'), 'escape' => false]);
        $form->addControlButtons(['sendTitle' => $this->lmsg('form_button_send'), 'cancelLink' => pm_Context::getModulesListUrl(),]);

        // Process the form - save the license key and run the installation scripts
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            $license_key = $form->getValue('license_key');

            if (!empty($license_key)) {
                pm_Settings::set('license_key', $license_key);

                $server_name = $form->getValue('server_name');
                pm_Settings::set('server_name', $server_name);

                if ($form->getValue('servers')) {
                    if ($this->runInstallation('servers', $license_key, $server_name)) {
                        pm_Settings::set('servers', $form->getValue('servers'));
                    }
                }

                if ($form->getValue('apm')) {
                    if ($this->runInstallation('apm', $license_key, $server_name)) {
                        pm_Settings::set('apm', $form->getValue('apm'));
                    }
                }

                $this->_status->addMessage('info', $this->lmsg('message_success'));

                // Reboot
                if ($form->getValue('reboot')) {
                    $this->rebootServer();
                }

                $this->_helper->json(['redirect' => pm_Context::getBaseUrl()]);
            }
        }

        $this->view->form = $form;
    }

    private function installationType($type, &$form)
    {
        // Not used at the moment until SDK supports graceful reboot without throwing an error message
        if ($type == 'reboot') {
            $form->addElement('description', 'type_reboot_logo_dummy', ['description' => $this->lmsg('form_type_reboot_logo_dummy'), 'escape' => false]);
            $form->addElement('description', 'reboot_dummy_installed', ['description' => $this->lmsg('form_reboot_dummy_installed'), 'escape' => false]);
            $form->addElement('checkbox', 'reboot', ['label' => $this->lmsg('form_reboot_server'), 'value' => '']);
            $form->addElement('description', 'reboot_description', ['description' => $this->lmsg('form_reboot_description'), 'escape' => false]);

            return;
        }

        $installation_done = $this->checkInstallationState($type);

        if ($type == 'servers') {
            $form->addElement('description', 'type_servers_logo', ['description' => $this->lmsg('form_type_servers_logo'), 'escape' => false]);
            if ($installation_done == false) {
                $form->addElement('description', 'servers_install', ['description' => $this->lmsg('form_type_servers_install'), 'escape' => false]);
                $form->addElement('checkbox', 'servers', ['label' => $this->lmsg('form_type_servers'), 'value' => pm_Settings::get('servers'), 'checked' => true]);
            } else {
                $form->addElement('description', 'servers_installed', ['description' => $this->lmsg('form_type_servers_installed'), 'escape' => false]);
                $form->addElement('checkbox', 'servers', ['label' => $this->lmsg('form_type_servers_reinstall'), 'value' => '', 'checked' => false]);
            }

            $form->addElement('description', 'type_servers_description', ['description' => $this->lmsg('form_type_servers_description'), 'escape' => false]);

            return;
        }

        if ($type == 'apm') {
            $form->addElement('description', 'type_apm_logo', ['description' => $this->lmsg('form_type_apm_logo'), 'escape' => false]);

            if ($installation_done == false) {
                $form->addElement('description', 'apm_install', ['description' => $this->lmsg('form_type_apm_install'), 'escape' => false]);
                $form->addElement('checkbox', 'apm', ['label' => $this->lmsg('form_type_apm'), 'value' => pm_Settings::get('apm'), 'checked' => true]);
            } else {
                $form->addElement('description', 'apm_installed', ['description' => $this->lmsg('form_type_apm_installed'), 'escape' => false]);
                $form->addElement('checkbox', 'apm', ['label' => $this->lmsg('form_type_apm_reinstall'), 'value' => '', 'checked' => false]);
            }

            $form->addElement('description', 'type_apm_description', ['description' => $this->lmsg('form_type_apm_description'), 'escape' => false]);
        }
    }

    private function checkInstallationState($type)
    {
        return pm_Settings::get($type);
    }

    private function runInstallation($type, $license_key, $server_name = '')
    {
        $options = array();

        $options[] = $license_key;
        $options[] = addslashes($server_name);

        $result = pm_ApiCli::callSbin($type.'.sh', $options, pm_ApiCli::RESULT_FULL);

        if ($result['code']) {
            throw new pm_Exception("{$result['stdout']}\n{$result['stderr']}");
        }

        return true;
    }

    private function rebootServer()
    {
        $request = "<server><reboot/></server>";

        pm_ApiRpc::getService()->call($request, 'admin');
    }
}
