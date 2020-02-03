-------------------------------------------------------------------------
--
--    mcdcmReporter - Radiology Image Management and Reporting System
--	  Copyright (c) 2016-2020 Kim-Ann Git
--  
--    This program is free software: you can redistribute it and/or modify
--    it under the terms of the GNU General Public License as published by
--    the Free Software Foundation, either version 3 of the License, or
--    (at your option) any later version.
--
--    This program is distributed in the hope that it will be useful,
--    but WITHOUT ANY WARRANTY; without even the implied warranty of
--    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
--    GNU General Public License for more details.
--
--    You should have received a copy of the GNU General Public License
--    along with this program.  If not, see <https://www.gnu.org/licenses/>.
--
---------------------------------------------------------------------------*/	

--[addPatient.lua]
--	  Calls addPatient.php whenever a study is stable
--	  Provides basic HTTP request filtering

function OnStableStudy(studyId, tags, metadata)
	local url = 'http://localhost/isen/addPatient.php?study=' .. studyId
	HttpGet(url)
	-- print('addPatient.php called: ' .. studyId)
end

-- https://book.orthanc-server.com/faq/security.html
function IncomingHttpRequestFilter(method, uri, ip, username, httpHeaders)
	if method == 'GET' then
		-- Allow GET from all IPs
		return true
	elseif ip == '127.0.0.1' then
		-- Allow any HTTP method coming from localhost
		return true
	else
		-- Access is disallowed by default
		return false
	end
end