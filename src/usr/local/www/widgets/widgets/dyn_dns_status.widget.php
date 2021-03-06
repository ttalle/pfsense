<?php
/*
	Original status page code from: services_dyndns.php
*/
/* ====================================================================
 *	Copyright (c)  2004-2015  Electric Sheep Fencing, LLC. All rights reserved.
 *  Copyright (c)  2013 Stanley P. Miller \ stan-qaz
 *
 *	Redistribution and use in source and binary forms, with or without modification,
 *	are permitted provided that the following conditions are met:
 *
 *	1. Redistributions of source code must retain the above copyright notice,
 *		this list of conditions and the following disclaimer.
 *
 *	2. Redistributions in binary form must reproduce the above copyright
 *		notice, this list of conditions and the following disclaimer in
 *		the documentation and/or other materials provided with the
 *		distribution.
 *
 *	3. All advertising materials mentioning features or use of this software
 *		must display the following acknowledgment:
 *		"This product includes software developed by the pfSense Project
 *		 for use in the pfSense software distribution. (http://www.pfsense.org/).
 *
 *	4. The names "pfSense" and "pfSense Project" must not be used to
 *		 endorse or promote products derived from this software without
 *		 prior written permission. For written permission, please contact
 *		 coreteam@pfsense.org.
 *
 *	5. Products derived from this software may not be called "pfSense"
 *		nor may "pfSense" appear in their names without prior written
 *		permission of the Electric Sheep Fencing, LLC.
 *
 *	6. Redistributions of any form whatsoever must retain the following
 *		acknowledgment:
 *
 *	"This product includes software developed by the pfSense Project
 *	for use in the pfSense software distribution (http://www.pfsense.org/).
 *
 *	THIS SOFTWARE IS PROVIDED BY THE pfSense PROJECT ``AS IS'' AND ANY
 *	EXPRESSED OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 *	IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 *	PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE pfSense PROJECT OR
 *	ITS CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 *	SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 *	NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 *	LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 *	HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
 *	STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 *	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
 *	OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *	====================================================================
 *
 */
/*
	pfSense_BUILDER_BINARIES:	/usr/bin/host
	pfSense_MODULE:	dyndns
*/

$nocsrf = true;

require_once("guiconfig.inc");
require_once("pfsense-utils.inc");
require_once("functions.inc");
require_once("/usr/local/www/widgets/include/dyn_dns_status.inc");

if (!is_array($config['dyndnses']['dyndns'])) {
	$config['dyndnses']['dyndns'] = array();
}

$a_dyndns = &$config['dyndnses']['dyndns'];

if($_REQUEST['getdyndnsstatus']) {
	$first_entry = true;
	foreach ($a_dyndns as $dyndns) {
		if ($first_entry) {
			$first_entry = false;
		} else {
			// Put a vertical bar delimiter between the echoed HTML for each entry processed.
			echo "|";
		}

		$filename = "{$g['conf_path']}/dyndns_{$dyndns['interface']}{$dyndns['type']}" . escapeshellarg($dyndns['host']) . "{$dyndns['id']}.cache";
		if (file_exists($filename)) {
			$ipaddr = dyndnsCheckIP($dyndns['interface']);
			$cached_ip_s = explode(':', file_get_contents($filename));
			$cached_ip = $cached_ip_s[0];
			if ($ipaddr <> $cached_ip) {
				echo "<font color='red'>";
			} else {
				echo "<font color='green'>";
			}
			echo htmlspecialchars($cached_ip);
			echo "</font>";
		} else {
			echo "N/A " . date("H:i:s");
		}
	}
	exit;
}

?>
<div class="content">
<table>
	<tr>
		<td width="5%"  class="listhdrr"><?=gettext("Int.");?></td>
		<td width="15%" class="listhdrr"><?=gettext("Service");?></td>
		<td width="20%" class="listhdrr"><?=gettext("Hostname");?></td>
		<td width="20%" class="listhdrr"><?=gettext("Cached IP");?></td>
	</tr>
	<?php $i = 0; foreach ($a_dyndns as $dyndns): ?>
	<tr ondblclick="document.location='services_dyndns_edit.php?id=<?=$i;?>'">
		<td class="listlr">
		<?php $iflist = get_configured_interface_with_descr();
		foreach ($iflist as $if => $ifdesc) {
			if ($dyndns['interface'] == $if) {
				if (!isset($dyndns['enable'])) {
					echo "<span class=\"gray\">{$ifdesc}</span>";
				} else {
					echo "{$ifdesc}";
				}
				break;
			}
		}
		$groupslist = return_gateway_groups_array();
		foreach ($groupslist as $if => $group) {
			if ($dyndns['interface'] == $if) {
				if (!isset($dyndns['enable'])) {
					echo "<span class=\"gray\">{$if}</span>";
				} else {
					echo "{$if}";
				}
				break;
			}
		}
		?>
		</td>
		<td class="listr">
		<?php
		$types = explode(",", DYNDNS_PROVIDER_DESCRIPTIONS);
		$vals = explode(" ", DYNDNS_PROVIDER_VALUES);
		for ($j = 0; $j < count($vals); $j++) {
			if ($vals[$j] == $dyndns['type']) {
				if (!isset($dyndns['enable'])) {
					echo "<span class=\"gray\">".htmlspecialchars($types[$j])."</span>";
				} else {
					echo htmlspecialchars($types[$j]);
				}
				break;
			}
		}
		?>
		</td>
		<td class="listr">
		<?php
		if (!isset($dyndns['enable'])) {
			echo "<span class=\"gray\">".htmlspecialchars($dyndns['host'])."</span>";
		} else {
			echo htmlspecialchars($dyndns['host']);
		}
		?>
		</td>
		<td class="listr">
		<div id='dyndnsstatus<?= $i;?>'><?= gettext("Checking ...");?></div>
		</td>
	</tr>
	<?php $i++; endforeach;?>
</table>
</div>
<script type="text/javascript">
//<![CDATA[
	function dyndns_getstatus() {
		scroll(0,0);
		var url = "/widgets/widgets/dyn_dns_status.widget.php";
		var pars = 'getdyndnsstatus=yes';
		jQuery.ajax(
			url,
			{
				type: 'get',
				data: pars,
				complete: dyndnscallback
			});
		// Refresh the status every 5 minutes
		setTimeout('dyndns_getstatus()', 5*60*1000);
	}
	function dyndnscallback(transport) {
		// The server returns a string of statuses separated by vertical bars
		var responseStrings = transport.responseText.split("|");
		for (var count=0; count<responseStrings.length; count++) {
			var divlabel = '#dyndnsstatus' + count;
			jQuery(divlabel).prop('innerHTML',responseStrings[count]);
		}
	}
	// Do the first status check 2 seconds after the dashboard opens
	setTimeout('dyndns_getstatus()', 2000);
//]]>
</script>
