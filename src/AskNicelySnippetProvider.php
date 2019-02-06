<?php

namespace Sminnee\AskNicely;

use SilverStripe\TagManager\SnippetProvider;
use FieldList;
use Member;

/**
 * A snippet provider that lets you add arbitrary HTML
 */
class AskNicelySnippetProvider implements SnippetProvider
{

    public function getTitle()
    {
        return "AskNicely Web Survey";
    }

    public function getParamFields()
    {
        return new FieldList(
            \TextField::create("DomainKey", "Domain Key")
                ->setDescription("'XXX' in 'XXX.asknice.ly'"),
            \TextField::create("HashKey", "Email Hashing Key")
                ->setDescription("Visit /setting/recommend/inapp in AskNicely"),
            \TextField::create("Segment", "Add to the following AskNicely segment"),
            \HeaderField::create("", "Debugging"),
            \CheckboxField::create("Force", "Force display")
                ->setDescription("Useful in debugging. Don't run in production!"),
            \TextField::create("ForceEmail", "Always use this email address")
                ->setDescription("Useful in debugging. Don't run in production!")
        );
    }

    public function getSummary(array $params)
    {
        return $this->getTitle() . " in  " . $params['Zone'];
    }

    public function getSnippets(array $params)
    {
        if ($params['ForceEmail']) {
            $email = $params['ForceEmail'];
            $name = "Forced Email";
            $createdTime = time();

        } elseif ($member = Member::currentUser()) {
            $email = $member->Email;
            $name = $member->FirstName . ' ' . $member->Surname;
            $createdTime = strtotime($member->Created);

        } else {
            return [];
        }

        $settings = [
            'domain_id' => $params['DomainKey'] . '.asknice.ly',
            'domain_key' => $params['DomainKey'],
            'name' => $name,
            'email' => $email,
            'email_hash' => hash_hmac('sha256', $email, $params['HashKey']),
            'created' => $createdTime,
            'mode' => 'docked',
        ];

        if ($params['Force']) {
            $settings['force'] = true;
        }

        if ($params['Segment']) {
            $settings['segment'] = $params['Segment'];
        }

        $settingsJson = json_encode($settings);

        $snippet = <<<HTML
<script type="text/javascript" >
    window.asknicelySettings = $settingsJson;
</script>
<script type="text/javascript" async src="https://live.asknice.ly/widgetv2.js"> </script>
HTML;

        return [
            'end-body' => $snippet
        ];

    }
}
