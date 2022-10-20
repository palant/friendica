<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Core\Session\Model;

use Friendica\Core\Session\Capability\IHandleSessions;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Contact;

class UserSession implements IHandleUserSessions
{
	/** @var IHandleSessions */
	private $session;
	/** @var int|bool saves the public Contact ID for later usage */
	protected $publicContactId = false;

	public function __construct(IHandleSessions $session)
	{
		$this->session = $session;
	}

	/** {@inheritDoc} */
	public function getLocalUserId()
	{
		if (!empty($this->session->get('authenticated')) && !empty($this->session->get('uid'))) {
			return intval($this->session->get('uid'));
		}

		return false;
	}

	/** {@inheritDoc} */
	public function getPublicContactId()
	{
		if (empty($this->publicContactId) && !empty($this->session->get('authenticated'))) {
			if (!empty($this->session->get('my_address'))) {
				// Local user
				$this->publicContactId = Contact::getIdForURL($this->session->get('my_address'), 0, false);
			} elseif (!empty($this->session->get('visitor_home'))) {
				// Remote user
				$this->publicContactId = Contact::getIdForURL($this->session->get('visitor_home'), 0, false);
			}
		} elseif (empty($this->session->get('authenticated'))) {
			$this->publicContactId = false;
		}

		return $this->publicContactId;
	}

	/** {@inheritDoc} */
	public function getRemoteUserId()
	{
		if (empty($this->session->get('authenticated'))) {
			return false;
		}

		if (!empty($this->session->get('visitor_id'))) {
			return (int)$this->session->get('visitor_id');
		}

		return false;
	}

	/** {@inheritDoc} */
	public function getRemoteContactID(int $uid): int
	{
		if (!empty($this->session->get('remote')[$uid])) {
			$remote = $this->session->get('remote')[$uid];
		} else {
			$remote = 0;
		}

		$local_user = !empty($this->session->get('authenticated')) ? $this->session->get('uid') : 0;

		if (empty($remote) && ($local_user != $uid) && !empty($my_address = $this->session->get('my_address'))) {
			$remote = Contact::getIdForURL($my_address, $uid, false);
		}

		return $remote;
	}

	/** {@inheritDoc} */
	public function getUserIDForVisitorContactID(int $cid): int
	{
		if (empty($this->session->get('remote'))) {
			return false;
		}

		return array_search($cid, $this->session->get('remote'));
	}

	/** {@inheritDoc} */
	public function isAuthenticated(): bool
	{
		return $this->session->get('authenticated', false);
	}

	/** {@inheritDoc} */
	public function setVisitorsContacts()
	{
		$this->session->set('remote', Contact::getVisitorByUrl($this->session->get('my_url')));
	}
}
