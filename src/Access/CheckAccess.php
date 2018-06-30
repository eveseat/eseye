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

            // 'meta' URI's. see: https://esi.evetech.net/ui/?version=meta
            '/ping'                                                           => 'public',

            // Generated using tools: php get_endpoints_and_scopes.php
            '/alliances/{alliance_id}/'                                       => 'public',
            '/alliances/{alliance_id}/corporations/'                          => 'public',
            '/alliances/names/'                                               => 'public',
            '/alliances/{alliance_id}/icons/'                                 => 'public',
            '/alliances/'                                                     => 'public',
            '/characters/{character_id}/assets/'                              => 'esi-assets.read_assets.v1',
            '/corporations/{corporation_id}/assets/'                          => 'esi-assets.read_corporation_assets.v1',
            '/characters/{character_id}/bookmarks/'                           => 'esi-bookmarks.read_character_bookmarks.v1',
            '/characters/{character_id}/bookmarks/folders/'                   => 'esi-bookmarks.read_character_bookmarks.v1',
            '/corporations/{corporation_id}/bookmarks/'                       => 'esi-bookmarks.read_corporation_bookmarks.v1',
            '/corporations/{corporation_id}/bookmarks/folders/'               => 'esi-bookmarks.read_corporation_bookmarks.v1',
            '/characters/{character_id}/calendar/'                            => 'esi-calendar.read_calendar_events.v1',
            '/characters/{character_id}/calendar/{event_id}/'                 => 'esi-calendar.read_calendar_events.v1',
            '/characters/{character_id}/calendar/{event_id}/attendees/'       => 'esi-calendar.read_calendar_events.v1',
            '/characters/{character_id}/stats/'                               => 'esi-characterstats.read.v1',
            '/characters/{character_id}/'                                     => 'public',
            '/characters/names/'                                              => 'public',
            '/characters/{character_id}/portrait/'                            => 'public',
            '/characters/{character_id}/corporationhistory/'                  => 'public',
            '/characters/{character_id}/chat_channels/'                       => 'esi-characters.read_chat_channels.v1',
            '/characters/{character_id}/medals/'                              => 'esi-characters.read_medals.v1',
            '/characters/{character_id}/standings/'                           => 'esi-characters.read_standings.v1',
            '/characters/{character_id}/agents_research/'                     => 'esi-characters.read_agents_research.v1',
            '/characters/{character_id}/blueprints/'                          => 'esi-characters.read_blueprints.v1',
            '/characters/{character_id}/fatigue/'                             => 'esi-characters.read_fatigue.v1',
            '/characters/{character_id}/notifications/contacts/'              => 'esi-characters.read_notifications.v1',
            '/characters/{character_id}/notifications/'                       => 'esi-characters.read_notifications.v1',
            '/characters/{character_id}/roles/'                               => 'esi-characters.read_corporation_roles.v1',
            '/characters/{character_id}/titles/'                              => 'esi-characters.read_titles.v1',
            '/characters/{character_id}/clones/'                              => 'esi-clones.read_clones.v1',
            '/characters/{character_id}/implants/'                            => 'esi-clones.read_implants.v1',
            '/characters/{character_id}/contacts/'                            => 'esi-characters.read_contacts.v1',
            '/corporations/{corporation_id}/contacts/'                        => 'esi-corporations.read_contacts.v1',
            '/alliances/{alliance_id}/contacts/'                              => 'esi-alliances.read_contacts.v1',
            '/characters/{character_id}/contacts/labels/'                     => 'esi-characters.read_contacts.v1',
            '/characters/{character_id}/contracts/'                           => 'esi-contracts.read_character_contracts.v1',
            '/characters/{character_id}/contracts/{contract_id}/items/'       => 'esi-contracts.read_character_contracts.v1',
            '/characters/{character_id}/contracts/{contract_id}/bids/'        => 'esi-contracts.read_character_contracts.v1',
            '/corporations/{corporation_id}/contracts/'                       => 'esi-contracts.read_corporation_contracts.v1',
            '/corporations/{corporation_id}/contracts/{contract_id}/items/'   => 'esi-contracts.read_corporation_contracts.v1',
            '/corporations/{corporation_id}/contracts/{contract_id}/bids/'    => 'esi-contracts.read_corporation_contracts.v1',
            '/corporations/{corporation_id}/shareholders/'                    => 'esi-wallet.read_corporation_wallets.v1',
            '/corporations/{corporation_id}/'                                 => 'public',
            '/corporations/{corporation_id}/alliancehistory/'                 => 'public',
            '/corporations/names/'                                            => 'public',
            '/corporations/{corporation_id}/members/'                         => 'esi-corporations.read_corporation_membership.v1',
            '/corporations/{corporation_id}/roles/'                           => 'esi-corporations.read_corporation_membership.v1',
            '/corporations/{corporation_id}/roles/history/'                   => 'esi-corporations.read_corporation_membership.v1',
            '/corporations/{corporation_id}/icons/'                           => 'public',
            '/corporations/npccorps/'                                         => 'public',
            '/corporations/{corporation_id}/structures/'                      => 'esi-corporations.read_structures.v1',
            '/corporations/{corporation_id}/membertracking/'                  => 'esi-corporations.track_members.v1',
            '/corporations/{corporation_id}/divisions/'                       => 'esi-corporations.read_divisions.v1',
            '/corporations/{corporation_id}/members/limit/'                   => 'esi-corporations.track_members.v1',
            '/corporations/{corporation_id}/titles/'                          => 'esi-corporations.read_titles.v1',
            '/corporations/{corporation_id}/members/titles/'                  => 'esi-corporations.read_titles.v1',
            '/corporations/{corporation_id}/blueprints/'                      => 'esi-corporations.read_blueprints.v1',
            '/corporations/{corporation_id}/standings/'                       => 'esi-corporations.read_standings.v1',
            '/corporations/{corporation_id}/starbases/'                       => 'esi-corporations.read_starbases.v1',
            '/corporations/{corporation_id}/starbases/{starbase_id}/'         => 'esi-corporations.read_starbases.v1',
            '/corporations/{corporation_id}/containers/logs/'                 => 'esi-corporations.read_container_logs.v1',
            '/corporations/{corporation_id}/facilities/'                      => 'esi-corporations.read_facilities.v1',
            '/corporations/{corporation_id}/medals/'                          => 'esi-corporations.read_medals.v1',
            '/corporations/{corporation_id}/medals/issued/'                   => 'esi-corporations.read_medals.v1',
            '/dogma/attributes/'                                              => 'public',
            '/dogma/attributes/{attribute_id}/'                               => 'public',
            '/dogma/effects/'                                                 => 'public',
            '/dogma/effects/{effect_id}/'                                     => 'public',
            '/fw/wars/'                                                       => 'public',
            '/fw/stats/'                                                      => 'public',
            '/fw/systems/'                                                    => 'public',
            '/fw/leaderboards/'                                               => 'public',
            '/fw/leaderboards/characters/'                                    => 'public',
            '/fw/leaderboards/corporations/'                                  => 'public',
            '/corporations/{corporation_id}/fw/stats/'                        => 'esi-corporations.read_fw_stats.v1',
            '/characters/{character_id}/fw/stats/'                            => 'esi-characters.read_fw_stats.v1',
            '/characters/{character_id}/fittings/'                            => 'esi-fittings.read_fittings.v1',
            '/fleets/{fleet_id}/'                                             => 'esi-fleets.read_fleet.v1',
            '/characters/{character_id}/fleet/'                               => 'esi-fleets.read_fleet.v1',
            '/fleets/{fleet_id}/members/'                                     => 'esi-fleets.read_fleet.v1',
            '/fleets/{fleet_id}/wings/'                                       => 'esi-fleets.read_fleet.v1',
            '/incursions/'                                                    => 'public',
            '/industry/facilities/'                                           => 'public',
            '/industry/systems/'                                              => 'public',
            '/characters/{character_id}/industry/jobs/'                       => 'esi-industry.read_character_jobs.v1',
            '/characters/{character_id}/mining/'                              => 'esi-industry.read_character_mining.v1',
            '/corporation/{corporation_id}/mining/observers/'                 => 'esi-industry.read_corporation_mining.v1',
            '/corporation/{corporation_id}/mining/observers/{observer_id}/'   => 'esi-industry.read_corporation_mining.v1',
            '/corporations/{corporation_id}/industry/jobs/'                   => 'esi-industry.read_corporation_jobs.v1',
            '/corporation/{corporation_id}/mining/extractions/'               => 'esi-industry.read_corporation_mining.v1',
            '/insurance/prices/'                                              => 'public',
            '/killmails/{killmail_id}/{killmail_hash}/'                       => 'public',
            '/characters/{character_id}/killmails/recent/'                    => 'esi-killmails.read_killmails.v1',
            '/corporations/{corporation_id}/killmails/recent/'                => 'esi-killmails.read_corporation_killmails.v1',
            '/characters/{character_id}/location/'                            => 'esi-location.read_location.v1',
            '/characters/{character_id}/ship/'                                => 'esi-location.read_ship_type.v1',
            '/characters/{character_id}/online/'                              => 'esi-location.read_online.v1',
            '/loyalty/stores/{corporation_id}/offers/'                        => 'public',
            '/characters/{character_id}/loyalty/points/'                      => 'esi-characters.read_loyalty.v1',
            '/characters/{character_id}/mail/'                                => 'esi-mail.read_mail.v1',
            '/characters/{character_id}/mail/labels/'                         => 'esi-mail.read_mail.v1',
            '/characters/{character_id}/mail/lists/'                          => 'esi-mail.read_mail.v1',
            '/characters/{character_id}/mail/{mail_id}/'                      => 'esi-mail.read_mail.v1',
            '/markets/prices/'                                                => 'public',
            '/markets/{region_id}/orders/'                                    => 'public',
            '/markets/{region_id}/history/'                                   => 'public',
            '/markets/structures/{structure_id}/'                             => 'esi-markets.structure_markets.v1',
            '/markets/groups/'                                                => 'public',
            '/markets/groups/{market_group_id}/'                              => 'public',
            '/characters/{character_id}/orders/'                              => 'esi-markets.read_character_orders.v1',
            '/markets/{region_id}/types/'                                     => 'public',
            '/corporations/{corporation_id}/orders/'                          => 'esi-markets.read_corporation_orders.v1',
            '/opportunities/groups/'                                          => 'public',
            '/opportunities/groups/{group_id}/'                               => 'public',
            '/opportunities/tasks/'                                           => 'public',
            '/opportunities/tasks/{task_id}/'                                 => 'public',
            '/characters/{character_id}/opportunities/'                       => 'esi-characters.read_opportunities.v1',
            '/characters/{character_id}/planets/'                             => 'esi-planets.manage_planets.v1',
            '/characters/{character_id}/planets/{planet_id}/'                 => 'esi-planets.manage_planets.v1',
            '/universe/schematics/{schematic_id}/'                            => 'public',
            '/corporations/{corporation_id}/customs_offices/'                 => 'esi-planets.read_customs_offices.v1',
            '/route/{origin}/{destination}/'                                  => 'public',
            '/characters/{character_id}/search/'                              => 'esi-search.search_structures.v1',
            '/search/'                                                        => 'public',
            '/characters/{character_id}/skillqueue/'                          => 'esi-skills.read_skillqueue.v1',
            '/characters/{character_id}/skills/'                              => 'esi-skills.read_skills.v1',
            '/characters/{character_id}/attributes/'                          => 'esi-skills.read_skills.v1',
            '/sovereignty/structures/'                                        => 'public',
            '/sovereignty/campaigns/'                                         => 'public',
            '/sovereignty/map/'                                               => 'public',
            '/status/'                                                        => 'public',
            '/universe/planets/{planet_id}/'                                  => 'public',
            '/universe/stations/{station_id}/'                                => 'public',
            '/universe/structures/{structure_id}/'                            => 'esi-universe.read_structures.v1',
            '/universe/systems/{system_id}/'                                  => 'public',
            '/universe/systems/'                                              => 'public',
            '/universe/types/{type_id}/'                                      => 'public',
            '/universe/types/'                                                => 'public',
            '/universe/groups/'                                               => 'public',
            '/universe/groups/{group_id}/'                                    => 'public',
            '/universe/categories/'                                           => 'public',
            '/universe/categories/{category_id}/'                             => 'public',
            '/universe/structures/'                                           => 'public',
            '/universe/races/'                                                => 'public',
            '/universe/factions/'                                             => 'public',
            '/universe/bloodlines/'                                           => 'public',
            '/universe/regions/'                                              => 'public',
            '/universe/regions/{region_id}/'                                  => 'public',
            '/universe/constellations/'                                       => 'public',
            '/universe/constellations/{constellation_id}/'                    => 'public',
            '/universe/moons/{moon_id}/'                                      => 'public',
            '/universe/stargates/{stargate_id}/'                              => 'public',
            '/universe/graphics/'                                             => 'public',
            '/universe/graphics/{graphic_id}/'                                => 'public',
            '/universe/system_jumps/'                                         => 'public',
            '/universe/system_kills/'                                         => 'public',
            '/universe/stars/{star_id}/'                                      => 'public',
            '/characters/{character_id}/wallet/'                              => 'esi-wallet.read_character_wallet.v1',
            '/characters/{character_id}/wallet/journal/'                      => 'esi-wallet.read_character_wallet.v1',
            '/characters/{character_id}/wallet/transactions/'                 => 'esi-wallet.read_character_wallet.v1',
            '/corporations/{corporation_id}/wallets/'                         => 'esi-wallet.read_corporation_wallets.v1',
            '/corporations/{corporation_id}/wallets/{division}/journal/'      => 'esi-wallet.read_corporation_wallets.v1',
            '/corporations/{corporation_id}/wallets/{division}/transactions/' => 'esi-wallet.read_corporation_wallets.v1',
            '/wars/'                                                          => 'public',
            '/wars/{war_id}/'                                                 => 'public',
            '/wars/{war_id}/killmails/'                                       => 'public',
        ],
        'post'   => [
            '/characters/{character_id}/assets/names/'         => 'esi-assets.read_assets.v1',
            '/characters/{character_id}/assets/locations/'     => 'esi-assets.read_assets.v1',
            '/corporations/{corporation_id}/assets/names/'     => 'esi-assets.read_corporation_assets.v1',
            '/corporations/{corporation_id}/assets/locations/' => 'esi-assets.read_corporation_assets.v1',
            '/characters/affiliation/'                         => 'public',
            '/characters/{character_id}/cspa/'                 => 'esi-characters.read_contacts.v1',
            '/characters/{character_id}/contacts/'             => 'esi-characters.write_contacts.v1',
            '/characters/{character_id}/fittings/'             => 'esi-fittings.write_fittings.v1',
            '/fleets/{fleet_id}/members/'                      => 'esi-fleets.write_fleet.v1',
            '/fleets/{fleet_id}/wings/'                        => 'esi-fleets.write_fleet.v1',
            '/fleets/{fleet_id}/wings/{wing_id}/squads/'       => 'esi-fleets.write_fleet.v1',
            '/characters/{character_id}/mail/'                 => 'esi-mail.send_mail.v1',
            '/characters/{character_id}/mail/labels/'          => 'esi-mail.organize_mail.v1',
            '/universe/names/'                                 => 'public',
            '/universe/ids/'                                   => 'public',
            '/ui/openwindow/marketdetails/'                    => 'esi-ui.open_window.v1',
            '/ui/openwindow/contract/'                         => 'esi-ui.open_window.v1',
            '/ui/openwindow/information/'                      => 'esi-ui.open_window.v1',
            '/ui/autopilot/waypoint/'                          => 'esi-ui.write_waypoint.v1',
            '/ui/openwindow/newmail/'                          => 'esi-ui.open_window.v1',
        ],
        'put'    => [
            '/characters/{character_id}/calendar/{event_id}/'           => 'esi-calendar.respond_calendar_events.v1',
            '/characters/{character_id}/contacts/'                      => 'esi-characters.write_contacts.v1',
            '/corporations/{corporation_id}/structures/{structure_id}/' => 'esi-corporations.write_structures.v1',
            '/fleets/{fleet_id}/'                                       => 'esi-fleets.write_fleet.v1',
            '/fleets/{fleet_id}/members/{member_id}/'                   => 'esi-fleets.write_fleet.v1',
            '/fleets/{fleet_id}/wings/{wing_id}/'                       => 'esi-fleets.write_fleet.v1',
            '/fleets/{fleet_id}/squads/{squad_id}/'                     => 'esi-fleets.write_fleet.v1',
            '/characters/{character_id}/mail/{mail_id}/'                => 'esi-mail.organize_mail.v1',
        ],
        'delete' => [
            '/characters/{character_id}/contacts/'               => 'esi-characters.write_contacts.v1',
            '/characters/{character_id}/fittings/{fitting_id}/'  => 'esi-fittings.write_fittings.v1',
            '/fleets/{fleet_id}/members/{member_id}/'            => 'esi-fleets.write_fleet.v1',
            '/fleets/{fleet_id}/wings/{wing_id}/'                => 'esi-fleets.write_fleet.v1',
            '/fleets/{fleet_id}/squads/{squad_id}/'              => 'esi-fleets.write_fleet.v1',
            '/characters/{character_id}/mail/labels/{label_id}/' => 'esi-mail.organize_mail.v1',
            '/characters/{character_id}/mail/{mail_id}/'         => 'esi-mail.organize_mail.v1',
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
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
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
