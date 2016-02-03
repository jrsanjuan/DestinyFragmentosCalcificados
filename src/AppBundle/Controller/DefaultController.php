<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Config\Definition\Exception\Exception;

class DefaultController extends Controller
{
    /**
     * Private variables
     */
    private $apiKey = '4e8336a211ec4d2e8286232280be9526';
    private $acounType = array(
        '1' => 'Xbox',
        '2' => 'Psn'
    );
    //Shamelessly stolen list of grimoire cards to check against from http://destinyghosthunter.net/
    private $fragments = array (
        700680 , 700690 , 700700 , 700710 , 700720 , 700730 , 700740 , 700750 , 700760 , 700770 ,
        700780 , 700790 , 700800 , 700810 , 700820 , 700830 , 700840 , 700850 , 700860 , 700870 ,
        700880 , 700890 , 700900 , 700910 , 700920 , 700930 , 700940 , 700950 , 700960 , 700970 ,
        700980 , 700990 , 701000 , 701010 , 701020 , 701030 , 701040 , 701050 , 701060 , 701070 ,
        701080 , 701090 , 701100 , 701110 , 701120 , 701130 , 701140 , 701150 , 701160 , 701170
    );

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        return $this->render('@App/index.html.twig');
    }

    public function newGuardianAction(Request $request)
    {
        if (!isset($_POST['gametarg'])) {
            throw new Exception('Otro');
        }

        $gametarg = $_POST['gametarg'];
        $platform = $_POST['platform'];

        return $this->redirectToRoute('guardian_fragments', array(
            'gametarg' => $gametarg,
            'platform' => strtolower($platform),
        ));
    }

    public function findCalcifiedFragmentsAction($gametarg, $platform)
    {
        $file = (__DIR__ . '/../../../web/cards.json');

        $fragmentsData = json_decode(file_get_contents($file), true);

        $BungieMembershipType = null;
        if ($platform == 'xbox')
            $BungieMembershipType = $this->acounType[1];
        elseif ($platform == 'playstation')
            $BungieMembershipType = $this->acounType[2];

        $user_id = $this->getUserId($gametarg, $BungieMembershipType);

        $user_grimoire_cards = $this->getUserGrimoireCards($BungieMembershipType, $user_id);

        $missinFragments = $this->fragments;

        foreach ($user_grimoire_cards as $card)
            if (in_array($card['cardId'], $this->fragments))
                $missinFragments = array_diff($missinFragments, array($card['cardId']));

        $missingFragmentsCount = count($missinFragments);
        $userFragmentsCount = 50 -$missingFragmentsCount;

        return $this->render('@App/fragments.html.twig', array(
            'user' => $gametarg,
            'data' => $fragmentsData,
            'missingFragmentsCount' => $missingFragmentsCount,
            'userFragments' => $userFragmentsCount,
            'missingFragments' => $missinFragments,
            'fragments' => $this->fragments,
            'cards' => $user_grimoire_cards,
        ));
    }

    private function getUserId($gametarg, $BungieMembershipType)
    {
        $base_url = 'http://www.bungie.net/Platform/Destiny/SearchDestinyPlayer/Tiger'.$BungieMembershipType.'/'.$gametarg.'/';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $base_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-API-Key: ' . $this->apiKey));

        $json = json_decode(curl_exec($ch), true);
        if (empty($json['Response']))
            throw new Exception('Guardian');
            
        return $json['Response'][0]['membershipId'];
    }

    private function getUserGrimoireCards($BungieMembershipType, $user_id)
    {
        $base_url = 'http://www.bungie.net/Platform/Destiny/Vanguard/Grimoire/Tiger'.$BungieMembershipType.'/'.$user_id.'/';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $base_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-API-Key: ' . $this->apiKey));

        $json = json_decode(curl_exec($ch), true);
        return $json['Response']['data']['cardCollection'];
    }
}
