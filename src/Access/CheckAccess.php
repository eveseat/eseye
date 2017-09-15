<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015, 2016, 2017  Leon Jacobs
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

namespace Seat\Eseye\Access;

use Seat\Eseye\Configuration;

/**
 * Class CheckAccess.
 * @package Seat\Eseye\Access
 */
class CheckAccess implements AccessInterface
{

    /**
     * @var array
     */
    protected $scope_map = [
        'get'    => [
            '/alliances/'                                                     => 'public',
            '/alliances/names/'                                               => 'public',
            '/alliances/{alliance_id}/'                                       => 'public',
            '/alliances/{alliance_id}/corporations/'                          => 'public',
            '/alliances/{alliance_id}/icons/'                                 => 'public',
            '/characters/names/'                                              => 'public',
            '/characters/{character_id}/'                                     => 'public',
            '/characters/{character_id}/assets/'                              => 'esi-assets.read_assets.v1',
            '/characters/{character_id}/bookmarks/'                           => 'esi-bookmarks.read_character_bookmarks.v1',
            '/characters/{character_id}/bookmarks/folders/'                   => 'esi-bookmarks.read_character_bookmarks.v1',
            '/characters/{character_id}/calendar/'                            => 'esi-calendar.read_calendar_events.v1',
            '/characters/{character_id}/calendar/{event_id}/'                 => 'esi-calendar.read_calendar_events.v1',
            '/characters/{character_id}/clones/'                              => 'esi-clones.read_clones.v1',
            '/characters/{character_id}/contacts/'                            => 'esi-characters.read_contacts.v1',
            '/characters/{character_id}/contacts/labels/'                     => 'esi-characters.read_contacts.v1',
            '/characters/{character_id}/corporationhistory/'                  => 'public',
            '/characters/{character_id}/killmails/recent/'                    => 'esi-killmails.read_killmails.v1',
            '/characters/{character_id}/location/'                            => 'esi-location.read_location.v1',
            '/characters/{character_id}/mail/'                                => 'esi-mail.read_mail.v1',
            '/characters/{character_id}/mail/labels/'                         => 'esi-mail.read_mail.v1',
            '/characters/{character_id}/mail/lists/'                          => 'esi-mail.read_mail.v1',
            '/characters/{character_id}/mail/{mail_id}/'                      => 'esi-mail.read_mail.v1',
            '/characters/{character_id}/planets/'                             => 'esi-planets.manage_planets.v1',
            '/characters/{character_id}/planets/{planet_id}/'                 => 'esi-planets.manage_planets.v1',
            '/characters/{character_id}/portrait/'                            => 'public',
            '/characters/{character_id}/search/'                              => 'esi-search.search_structures.v1',
            '/characters/{character_id}/ship/'                                => 'esi-location.read_ship_type.v1',
            '/characters/{character_id}/skillqueue/'                          => 'esi-skills.read_skillqueue.v1',
            '/characters/{character_id}/skills/'                              => 'esi-skills.read_skills.v1',
            '/characters/{character_id}/wallet/'                              => 'esi-wallet.read_character_wallet.v1',
            '/characters/{character_id}/wallet/journal/'                      => 'esi-wallet.read_character_wallet.v1',
            '/characters/{character_id}/wallet/transactions/'                 => 'esi-wallet.read_character_wallet.v1',
            '/corporations/names/'                                            => 'public',
            '/corporations/{corporation_id}/'                                 => 'public',
            '/corporations/{corporation_id}/alliancehistory/'                 => 'public',
            '/corporations/{corporation_id}/icons/'                           => 'public',
            '/corporations/{corporation_id}/members/'                         => 'esi-corporations.read_corporation_membership.v1',
            '/corporations/{corporation_id}/roles/'                           => 'esi-corporations.read_corporation_membership.v1',
            '/corporations/{corporation_id}/wallets/'                         => 'esi-wallet.read_corporation_wallets.v1',
            '/corporations/{corporation_id}/wallets/{division}/journal/'      => 'esi-wallet.read_corporation_wallets.v1',
            '/corporations/{corporation_id}/wallets/{division}/transactions/' => 'esi-wallet.read_corporation_wallets.v1',
            '/fleets/{fleet_id}/'                                             => 'esi-fleets.read_fleet.v1',
            '/fleets/{fleet_id}/members/'                                     => 'esi-fleets.read_fleet.v1',
            '/fleets/{fleet_id}/wings/'                                       => 'esi-fleets.read_fleet.v1',
            '/incursions/'                                                    => 'public',
            '/industry/facilities/'                                           => 'public',
            '/industry/systems/'                                              => 'public',
            '/insurance/prices/'                                              => 'public',
            '/killmails/{killmail_id}/{killmail_hash}/'                       => 'public',
            '/markets/prices/'                                                => 'public',
            '/markets/{region_id}/history/'                                   => 'public',
            '/markets/{region_id}/orders/'                                    => 'public',
            '/search/'                                                        => 'public',
            '/sovereignty/campaigns/'                                         => 'public',
            '/sovereignty/structures/'                                        => 'public',
            '/status/'                                                        => 'public',
            '/universe/schematics/{schematic_id}/'                            => 'public',
            '/universe/stations/{station_id}/'                                => 'public',
            '/universe/structures/'                                           => 'public',
            '/universe/structures/{structure_id}/'                            => 'esi-universe.read_structures.v1',
            '/universe/systems/{system_id}/'                                  => 'public',
            '/universe/types/{type_id}/'                                      => 'public',
            '/wars/'                                                          => 'public',
            '/wars/{war_id}/'                                                 => 'public',
            '/wars/{war_id}/killmails/'                                       => 'public',
        ],
        'post'   => [
            '/characters/{character_id}/contacts/'       => 'esi-characters.write_contacts.v1',
            '/characters/{character_id}/cspa/'           => 'esi-characters.read_contacts.v1',
            '/characters/{character_id}/mail/'           => 'esi-mail.send_mail.v1',
            '/characters/{character_id}/mail/labels/'    => 'esi-mail.organize_mail.v1',
            '/fleets/{fleet_id}/members/'                => 'esi-fleets.write_fleet.v1',
            '/fleets/{fleet_id}/wings/'                  => 'esi-fleets.write_fleet.v1',
            '/fleets/{fleet_id}/wings/{wing_id}/squads/' => 'esi-fleets.write_fleet.v1',
            '/ui/autopilot/waypoint/'                    => 'esi-ui.write_waypoint.v1',
            '/ui/openwindow/contract/'                   => 'esi-ui.open_window.v1',
            '/ui/openwindow/information/'                => 'esi-ui.open_window.v1',
            '/ui/openwindow/marketdetails/'              => 'esi-ui.open_window.v1',
            '/ui/openwindow/newmail/'                    => 'esi-ui.open_window.v1',
            '/universe/names/'                           => 'public',
        ],
        'put'    => [
            '/characters/{character_id}/calendar/{event_id}/' => 'esi-calendar.respond_calendar_events.v1',
            '/characters/{character_id}/contacts/'            => 'esi-characters.write_contacts.v1',
            '/characters/{character_id}/mail/{mail_id}/'      => 'esi-mail.organize_mail.v1',
            '/fleets/{fleet_id}/'                             => 'esi-fleets.write_fleet.v1',
            '/fleets/{fleet_id}/members/{member_id}/'         => 'esi-fleets.write_fleet.v1',
            '/fleets/{fleet_id}/squads/{squad_id}/'           => 'esi-fleets.write_fleet.v1',
            '/fleets/{fleet_id}/wings/{wing_id}/'             => 'esi-fleets.write_fleet.v1',
        ],
        'delete' => [
            '/characters/{character_id}/contacts/'               => 'esi-characters.write_contacts.v1',
            '/characters/{character_id}/mail/labels/{label_id}/' => 'esi-mail.organize_mail.v1',
            '/characters/{character_id}/mail/{mail_id}/'         => 'esi-mail.organize_mail.v1',
            '/fleets/{fleet_id}/members/{member_id}/'            => 'esi-fleets.write_fleet.v1',
            '/fleets/{fleet_id}/squads/{squad_id}/'              => 'esi-fleets.write_fleet.v1',
            '/fleets/{fleet_id}/wings/{wing_id}/'                => 'esi-fleets.write_fleet.v1',
        ],
        'patch'  => [
        ],
    ];

    /**
     * @param string $method
     * @param string $uri
     * @param array  $scopes
     *
     * @return bool|mixed
     */
    public function can(string $method, string $uri, array $scopes): bool
    {

        if (! array_key_exists($uri, $this->scope_map[$method])) {

            Configuration::getInstance()->getLogger()
                ->warning('An unknown URI was called. Allowing ' . $uri);

            return true;
        }

        $required_scope = $this->scope_map[$method][$uri];

        // Public scopes require no authentication!
        if ($required_scope == 'public')
            return true;

        if (! in_array($required_scope, $scopes))
            return false;

        return true;
    }
}
