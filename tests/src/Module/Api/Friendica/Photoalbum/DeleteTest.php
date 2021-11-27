<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Test\src\Module\Api\Friendica\Photoalbum;

use Friendica\DI;
use Friendica\Module\Api\Friendica\Photoalbum\Delete;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Test\src\Module\Api\ApiTest;

class DeleteTest extends ApiTest
{
	public function testEmpty()
	{
		$this->expectException(BadRequestException::class);
		(new Delete(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))->run();

	}

	public function testWrong()
	{
		$this->expectException(BadRequestException::class);
		(new Delete(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))->run(['album' => 'album_name']);
	}

	public function testValid()
	{
		self::markTestIncomplete('We need to add a dataset for this.');
	}
}