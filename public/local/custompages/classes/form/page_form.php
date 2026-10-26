<?php
// This file is part of Moodle - https://moodle.org/

namespace local_custompages\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

/**
 * Form for registering a provider page.
 */
class page_form extends \moodleform {

    /**
     * Define form elements.
     */
    public function definition(): void {
        $mform = $this->_form;
        $providers = $this->_customdata['providers'] ?? [];
        $roles = $this->_customdata['roles'] ?? [];

        // Keep the registration ID in submitted data so edit validation can exclude it.
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'title',
            get_string('pagetitle', 'local_custompages'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');

        $mform->addElement('text', 'slug',
            get_string('pageslug', 'local_custompages'));
        $mform->setType('slug', PARAM_ALPHANUMEXT);
        $mform->addRule('slug', null, 'required', null, 'client');

        $mform->addElement('select', 'provider',
            get_string('providerplugin', 'local_custompages'), $providers);
        $mform->addRule('provider', null, 'required', null, 'client');

        $mform->addElement('text', 'providerslug',
            get_string('providerpageslug', 'local_custompages'));
        $mform->setType('providerslug', PARAM_ALPHANUMEXT);
        $mform->addRule('providerslug', null, 'required', null, 'client');

        $mform->addElement('select', 'roleids',
            get_string('allowedroles', 'local_custompages'), $roles,
            ['multiple' => true, 'size' => 8]);
        $mform->setType('roleids', PARAM_INT);
        $mform->addHelpButton('roleids', 'allowedroles', 'local_custompages');

        $mform->addElement('advcheckbox', 'visible',
            get_string('visible', 'local_custompages'));
        $mform->setDefault('visible', 1);

        $mform->addElement('text', 'sortorder',
            get_string('sortorder', 'local_custompages'));
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 0);

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Validate that the chosen provider declares the requested page.
     *
     * @param array $data Submitted form data.
     * @param array $files Submitted files.
     * @return array Validation errors.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $provider = $data['provider'] ?? '';
        $providerslug = $data['providerslug'] ?? '';
        $id = (int)($data['id'] ?? 0);

        if (!array_key_exists($provider, \local_custompages\manager::get_providers())) {
            $errors['provider'] = get_string(
                'invalidprovider',
                'local_custompages'
            );
        } else {
            $pages = \local_custompages\manager::get_provider_pages($provider);

            if (!isset($pages[$providerslug])) {
                $errors['providerslug'] = get_string(
                    'invalidproviderpage',
                    'local_custompages'
                );
            }
        }

        if (!empty($data['slug']) &&
                \local_custompages\manager::slug_exists($data['slug'], $id ?: null)) {
            $errors['slug'] = get_string('duplicateslug', 'local_custompages');
        }

        return $errors;
    }
}
