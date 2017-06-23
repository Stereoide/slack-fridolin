<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FridolinController extends Controller
{
    function fetchFridolinResults($keyword)
    {
        $url = 'https://fridolin.otterbach.de/searchVCardsHandler.php?search=' . urlencode($keyword);

        $html = file_get_contents($url);

        /* Split raw HTML into individual vcard segments */

        $vcards = [];
        while (true) {
            $strpos = strpos($html, '<div class="vcard">', 1);
            if (false === $strpos) {
                $vcards[] = $html;
                break;
            } else {
                $vcards[] = substr($html, 0, $strpos);
                $html = substr($html, $strpos);
            }
        }

        /* Process vcard segments */

        $contacts = [];

        foreach ($vcards as $html) {
            preg_match('/\<div class\="vcard"\>(.*)\<\/div\>/', $html, $matches);
            if (!isset($matches[1])) {
                return null;
            }
            $vcard = $matches[1];

            preg_match('/\<div class\="infos"\>(.*)\<\/div\>/', $vcard, $matches);
            if (!isset($matches[1])) {
                return null;
            }
            $infos = $matches[1];

            $contact = array();

            /* Name */

            preg_match('/\<div class\="info name"\>\<div class\="value selectable"\>(.*?)\<div/', $infos, $matches);
            if (!isset($matches[1])) {
                return null;
            }
            $contact['name'] = $matches[1];

            /* Phones */

            preg_match_all('/\<div class\=".*? phone"\>\<div class\="label"\>(.*?)\<\/div\>\<div class\=".*?"\>\<a href\=".*?" \>(.*?)\<\/a\>\<\/div\>/', $infos, $matches);
            if (empty($matches[1])) {
                return null;
            }

            $contact['phones'] = [];
            foreach ($matches[1] as $index => $label) {
                $contact['phones'][] = ['label' => $label, 'number' => $matches[2][$index]];
            }

            /* Mails */

            preg_match_all('/\<div class\=".*? mail"\>\<div class\="label"\>(.*?)\<\/div\>\<div class\=".*?"\>\<a href\=".*?"\>(.*?)\<\/a\>\<\/div\>/', $infos, $matches);
            if (empty($matches[1])) {
                return null;
            }

            $contact['mails'] = [];
            foreach ($matches[1] as $index => $label) {
                $contact['mails'][] = ['label' => $label, 'address' => $matches[2][$index]];
            }

            $contacts[] = $contact;
        }

        /* Return contacts */

        return $contacts;
    }
}
