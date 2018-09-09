<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015, 2016, 2017, 2018  Leon Jacobs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

// If you are reading this, prepare the eye bleach! This is absolutely
// some of the shittest PHP you will _ever_ read. It is mostly because
// we want to have everyting in a single file, making it easy to run
// using the tokenegenerator command. Still, its terrible, and I know.

session_start();

// Helpers

/**
 * Redirect a request to the start of this script.
 */
function redirect_to_new()
{

    header('Location: ' . $_SERVER['PHP_SELF'] . '?action=new');
    die();
}

/**
 * @return string
 */
function get_sso_callback_url()
{

    if (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        $protocol = 'https://';
    else
        $protocol = 'http://';

    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?action=eveonlinecallback';
}

// UI Parts
/**
 * @return string
 */
function get_header()
{

    return <<<'EOF'
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->

    <title>New ESI Refresh Token</title>

    <!-- Bootstrap core CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

    <!-- Custom styles for this template -->
    <style type='text/css'>.header,body{padding-bottom:20px}.header,.jumbotron{border-bottom:1px solid #e5e5e5}body{padding-top:20px}.footer,.header,.marketing{padding-right:15px;padding-left:15px}.header h3{margin-top:0;margin-bottom:0;line-height:40px}.footer{padding-top:19px;color:#777;border-top:1px solid #e5e5e5}@media (min-width:768px){.container{max-width:730px}}.container-narrow>hr{margin:30px 0}.jumbotron{text-align:center}.jumbotron .btn{padding:14px 24px;font-size:21px}.marketing{margin:40px 0}.marketing p+h4{margin-top:28px}@media screen and (min-width:768px){.footer,.header,.marketing{padding-right:0;padding-left:0}.header{margin-bottom:30px}.jumbotron{border-bottom:0}}</style>

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>

  <body>

    <div class="container">
      <div class="header clearfix">
        <h3 class="text-muted">ESI Refresh Token Generator</h3>
      </div>
EOF;

}

/**
 * @return string
 */
function get_footer()
{

    return <<<'EOF'
    </div> <!-- /container -->
  </body>
</html>
EOF;

}

// Page contents

/**
 * Fresh, new login page.
 */
function new_login()
{

    $action = $_SERVER['PHP_SELF'] . '?action=submitsecrets';
    $callback = get_sso_callback_url();

    echo get_header();
    echo <<<EOF
      <div class="jumbotron">
        <p>
          Create a new Application on the
          <a href="https://developers.eveonline.com/applications/create" target="_blank">EVE Online Developers Site</a>.
          Use the resultant <b>Client ID</b> and <b>Secret Key</b> in the form below.
        </p>
        <p>
        The callback url to use in the application form is: <pre>$callback</pre>
        </p>
      </div>

      <div class="row marketing">

        <form action="$action" method="post" class="form-horizontal">
        <fieldset>

        <!-- Text input-->
        <div class="form-group">
          <label class="col-md-4 control-label" for="clientid">Client ID</label>
          <div class="col-md-4">
          <input id="clientid" name="clientid" type="text" placeholder="Client ID" class="form-control input-md">
          <span class="help-block">ClientID From the EVE Online Developers Site</span>
          </div>
        </div>

        <!-- Password input-->
        <div class="form-group">
          <label class="col-md-4 control-label" for="secret">Secret</label>
          <div class="col-md-4">
            <input id="secret" name="secret" type="password" placeholder="Secret" class="form-control input-md">
            <span class="help-block">Secret From the EVE Online Developers Site</span>
          </div>
        </div>
        
        <!-- Select Multiple -->
        <div class="form-group">
          <label class="col-md-4 control-label" for="scopes">Scopes</label>
          <div class="col-md-4">
            <select id="scopes" name="scopes[]" class="form-control" multiple="multiple">

            <!-- in the tools directory, run: -->
            <!-- php get_endpoints_and_scopes.php | grep "|" | cut -d"|" -f 3 | sort | uniq | grep -v public | awk '{ print "<option value=\"" $1 "\">" $1 "</option>"}' -->
            <!-- done :D -->

<option value="esi-alliances.read_contacts.v1">esi-alliances.read_contacts.v1</option>
<option value="esi-assets.read_assets.v1">esi-assets.read_assets.v1</option>
<option value="esi-assets.read_corporation_assets.v1">esi-assets.read_corporation_assets.v1</option>
<option value="esi-bookmarks.read_character_bookmarks.v1">esi-bookmarks.read_character_bookmarks.v1</option>
<option value="esi-bookmarks.read_corporation_bookmarks.v1">esi-bookmarks.read_corporation_bookmarks.v1</option>
<option value="esi-calendar.read_calendar_events.v1">esi-calendar.read_calendar_events.v1</option>
<option value="esi-calendar.respond_calendar_events.v1">esi-calendar.respond_calendar_events.v1</option>
<option value="esi-characters.read_agents_research.v1">esi-characters.read_agents_research.v1</option>
<option value="esi-characters.read_blueprints.v1">esi-characters.read_blueprints.v1</option>
<option value="esi-characters.read_chat_channels.v1">esi-characters.read_chat_channels.v1</option>
<option value="esi-characters.read_contacts.v1">esi-characters.read_contacts.v1</option>
<option value="esi-characters.read_corporation_roles.v1">esi-characters.read_corporation_roles.v1</option>
<option value="esi-characters.read_fatigue.v1">esi-characters.read_fatigue.v1</option>
<option value="esi-characters.read_fw_stats.v1">esi-characters.read_fw_stats.v1</option>
<option value="esi-characters.read_loyalty.v1">esi-characters.read_loyalty.v1</option>
<option value="esi-characters.read_medals.v1">esi-characters.read_medals.v1</option>
<option value="esi-characters.read_notifications.v1">esi-characters.read_notifications.v1</option>
<option value="esi-characters.read_opportunities.v1">esi-characters.read_opportunities.v1</option>
<option value="esi-characters.read_standings.v1">esi-characters.read_standings.v1</option>
<option value="esi-characters.read_titles.v1">esi-characters.read_titles.v1</option>
<option value="esi-characters.write_contacts.v1">esi-characters.write_contacts.v1</option>
<option value="esi-characterstats.read.v1">esi-characterstats.read.v1</option>
<option value="esi-clones.read_clones.v1">esi-clones.read_clones.v1</option>
<option value="esi-clones.read_implants.v1">esi-clones.read_implants.v1</option>
<option value="esi-contracts.read_character_contracts.v1">esi-contracts.read_character_contracts.v1</option>
<option value="esi-contracts.read_corporation_contracts.v1">esi-contracts.read_corporation_contracts.v1</option>
<option value="esi-corporations.read_blueprints.v1">esi-corporations.read_blueprints.v1</option>
<option value="esi-corporations.read_contacts.v1">esi-corporations.read_contacts.v1</option>
<option value="esi-corporations.read_container_logs.v1">esi-corporations.read_container_logs.v1</option>
<option value="esi-corporations.read_corporation_membership.v1">esi-corporations.read_corporation_membership.v1</option>
<option value="esi-corporations.read_divisions.v1">esi-corporations.read_divisions.v1</option>
<option value="esi-corporations.read_facilities.v1">esi-corporations.read_facilities.v1</option>
<option value="esi-corporations.read_fw_stats.v1">esi-corporations.read_fw_stats.v1</option>
<option value="esi-corporations.read_medals.v1">esi-corporations.read_medals.v1</option>
<option value="esi-corporations.read_standings.v1">esi-corporations.read_standings.v1</option>
<option value="esi-corporations.read_starbases.v1">esi-corporations.read_starbases.v1</option>
<option value="esi-corporations.read_structures.v1">esi-corporations.read_structures.v1</option>
<option value="esi-corporations.read_titles.v1">esi-corporations.read_titles.v1</option>
<option value="esi-corporations.track_members.v1">esi-corporations.track_members.v1</option>
<option value="esi-corporations.write_structures.v1">esi-corporations.write_structures.v1</option>
<option value="esi-fittings.read_fittings.v1">esi-fittings.read_fittings.v1</option>
<option value="esi-fittings.write_fittings.v1">esi-fittings.write_fittings.v1</option>
<option value="esi-fleets.read_fleet.v1">esi-fleets.read_fleet.v1</option>
<option value="esi-fleets.write_fleet.v1">esi-fleets.write_fleet.v1</option>
<option value="esi-industry.read_character_jobs.v1">esi-industry.read_character_jobs.v1</option>
<option value="esi-industry.read_character_mining.v1">esi-industry.read_character_mining.v1</option>
<option value="esi-industry.read_corporation_jobs.v1">esi-industry.read_corporation_jobs.v1</option>
<option value="esi-industry.read_corporation_mining.v1">esi-industry.read_corporation_mining.v1</option>
<option value="esi-killmails.read_corporation_killmails.v1">esi-killmails.read_corporation_killmails.v1</option>
<option value="esi-killmails.read_killmails.v1">esi-killmails.read_killmails.v1</option>
<option value="esi-location.read_location.v1">esi-location.read_location.v1</option>
<option value="esi-location.read_online.v1">esi-location.read_online.v1</option>
<option value="esi-location.read_ship_type.v1">esi-location.read_ship_type.v1</option>
<option value="esi-mail.organize_mail.v1">esi-mail.organize_mail.v1</option>
<option value="esi-mail.read_mail.v1">esi-mail.read_mail.v1</option>
<option value="esi-mail.send_mail.v1">esi-mail.send_mail.v1</option>
<option value="esi-markets.read_character_orders.v1">esi-markets.read_character_orders.v1</option>
<option value="esi-markets.read_corporation_orders.v1">esi-markets.read_corporation_orders.v1</option>
<option value="esi-markets.structure_markets.v1">esi-markets.structure_markets.v1</option>
<option value="esi-planets.manage_planets.v1">esi-planets.manage_planets.v1</option>
<option value="esi-planets.read_customs_offices.v1">esi-planets.read_customs_offices.v1</option>
<option value="esi-search.search_structures.v1">esi-search.search_structures.v1</option>
<option value="esi-skills.read_skillqueue.v1">esi-skills.read_skillqueue.v1</option>
<option value="esi-skills.read_skills.v1">esi-skills.read_skills.v1</option>
<option value="esi-ui.open_window.v1">esi-ui.open_window.v1</option>
<option value="esi-ui.write_waypoint.v1">esi-ui.write_waypoint.v1</option>
<option value="esi-universe.read_structures.v1">esi-universe.read_structures.v1</option>
<option value="esi-wallet.read_character_wallet.v1">esi-wallet.read_character_wallet.v1</option>
<option value="esi-wallet.read_corporation_wallets.v1">esi-wallet.read_corporation_wallets.v1</option>

            </select>
          </div>
        </div>

        <!-- Button -->
        <div class="form-group">
          <label class="col-md-4 control-label" for="login"></label>
          <div class="col-md-4">
            <button id="login" name="login" class="btn btn-primary">Generate Login</button>
          </div>
        </div>

        </fieldset>
        </form>

      </div>
EOF;
    echo get_footer();

}

/**
 * @param $url
 */
function print_sso_url($url)
{

    echo get_header();
    echo <<<EOF
      <div class="jumbotron">
        <p>
          Click the button below to login with your EVE Online account.<br>
          <a href="$url">
            <img src="https://images.contentful.com/idjq7aai9ylm/18BxKSXCymyqY4QKo8KwKe/c2bdded6118472dd587c8107f24104d7/EVE_SSO_Login_Buttons_Small_White.png?w=195&h=30" />
          </a>
        </p>
        <p>
          The generated URL is:
          <pre>$url</pre>
        </p>
      </div>
EOF;
    echo get_footer();

}

/**
 * @param $access_token
 * @param $refresh_token
 */
function print_tokens($access_token, $refresh_token)
{

    $start_again_url = $_SERVER['PHP_SELF'] . '?action=new';

    echo get_header();
    echo <<<EOF
      <div class="jumbotron">
        <p>
          Your current access token is: <pre>$access_token</pre><br>
          Valid for ~20 minutes.
        </p>
        <p>
          Your refresh token is: <pre>$refresh_token</pre><br>
          Valid until you delete the app from your account
          <a href="https://community.eveonline.com/support/third-party-applications/">here</a>.
        </p>
        <a class="btn btn-lg btn-success" href="$start_again_url" role="button">Start Again</a>
      </div>
EOF;
    echo get_footer();
}

// Ensure we have an action!
if (! isset($_GET['action']))
    redirect_to_new();

// Worlds most caveman router!

// Decide where to go based on the value of 'action'
switch ($_GET['action']) {

    // Display the form to create a new login.
    case 'new':
        $_SESSION['test'] = 'bob';
        new_login();
        break;

    case 'submitsecrets':
        // Ensure we got some values
        if (! isset($_REQUEST['clientid']) ||
            ! isset($_REQUEST['secret']) ||
            ! isset($_REQUEST['scopes'])
        ) {

            echo 'All fields are mandatory!<br>' . PHP_EOL;
            echo '<a href="' . $_SERVER['PHP_SELF'] . '?action=new">Start again</a>';

            die();
        }

        $_SESSION['clientid'] = $_REQUEST['clientid'];
        $_SESSION['secret'] = $_REQUEST['secret'];
        $_SESSION['state'] = uniqid();

        // Generate the url with the requested scopes
        $url = 'https://login.eveonline.com/oauth/authorize/?response_type=code&redirect_uri=' .
            urlencode(get_sso_callback_url()) . '&client_id=' .
            $_SESSION['clientid'] . '&scope=' . implode(' ', $_REQUEST['scopes']) . ' &state=' . $_SESSION['state'];

        // Print the HTML with the login button.
        print_sso_url($url);
        break;

    case 'eveonlinecallback':
        // Verify the state.
        if ($_REQUEST['state'] != $_SESSION['state']) {

            echo 'Invalid State! You will have to start again!<br>';
            echo '<a href="' . $_SERVER['PHP_SELF'] . '?action=new">Start again</a>';
            die();
        }

        // Clear the state value.
        $_SESSION['state'] = null;

        // Prep the authentication header.
        $headers = [
            'Authorization: Basic ' . base64_encode($_SESSION['clientid'] . ':' . $_SESSION['secret']),
            'Content-Type: application/json',
        ];

        // Seems like CCP does not mind JSON in the body. Yay.
        $fields = json_encode([
            'grant_type' => 'authorization_code',
            'code'       => $_REQUEST['code'],
        ]);

        // Start a cURL session
        $ch = curl_init('https://login.eveonline.com/oauth/token');
        curl_setopt_array($ch, [
                CURLOPT_URL             => 'https://login.eveonline.com/oauth/token',
                CURLOPT_POST            => true,
                CURLOPT_POSTFIELDS      => $fields,
                CURLOPT_HTTPHEADER      => $headers,
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_USERAGENT       => 'eseye/tokengenerator',
                CURLOPT_SSL_VERIFYPEER  => true,
                CURLOPT_SSL_CIPHER_LIST => 'TLSv1',
            ]
        );

        $result = curl_exec($ch);

        $data = json_decode($result);

        print_tokens($data->access_token, $data->refresh_token);
        break;

    // If we dont know what 'action' to perform, then redirect.
    default:
        redirect_to_new();
        break;
}
