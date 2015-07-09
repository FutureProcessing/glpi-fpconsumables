<?php

class PluginFpconsumablesCommon extends CommonDBTM {

   /**
    * Count how many assigned consumables items have user.
    * @param int $user_id
    * @return int
    */
   static function countForUserConsumables($user_id) {
        global $DB;

        $user_id = (int) $user_id;
        $consumable = 'Consumable';
		$consumableItem = 'ConsumableItem';

		$consumableTable = getTableForItemType($consumable);
		$consumableItemTable = getTableForItemType($consumableItem);

        $query = "SELECT COUNT($consumableItemTable.id)
				FROM $consumableTable
				LEFT JOIN $consumableItemTable ON ($consumableItemTable.id = $consumableTable.consumableitems_id)
				WHERE
					$consumableTable.itemtype = 'User'
					AND $consumableTable.items_id = '$user_id'";

        $result = $DB->query($query);

        if ($DB->numrows($result) != 0) {
            return $DB->result($result, 0, 0);
        }

        return 0;
    }

	/**
	 * Defining the name of the tab
	 */
	function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
		if ($item->getType() == 'User') {
            if (!$withtemplate) {
                $nb = 0;

                if ($_SESSION['glpishow_count_on_tabs']) {
                    $nb = self::countForUserConsumables($item->getID());
                }

                return array(
                    1 => self::createTabEntry(Consumable::getTypeName(2), $nb)
                );
            }
		}

		return '';
	}

	/**
	 * Defining the content of the tab. Here, a tab appeared in Administration > Profile for the current profile
	 */
	static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
		if ($item->getType() != 'User') {
			return true;
		}

		global $DB;

		$consumable = 'Consumable';
		$consumableItem = 'ConsumableItem';

		$consumableTable = getTableForItemType($consumable);
		$consumableItemTable = getTableForItemType($consumableItem);

		$ID = $item->getField('id');

		echo "<div class='spaced'><table class='tab_cadre_fixehov'>";
		$header = "<tr><th>".__('Type')."</th>";
		$header .= "<th>".__('Entity')."</th>";
		$header .= "<th>".__('Name')."</th>";
		$header .= "<th>".__('Add date')."</th>";
		$header .= "<th>".__('Use date')."</th>";
		$header .= "<th>".__('Status')."</th></tr>";
		echo $header;

		$item = getItemForItemtype($consumable);

		if ($item->canView()) {

			$query = "SELECT
					$consumableItemTable.id,
					$consumableItemTable.name,
					$consumableTable.date_in,
					$consumableTable.date_out,
					$consumableItemTable.entities_id
				FROM $consumableTable
				LEFT JOIN $consumableItemTable ON ($consumableItemTable.id = $consumableTable.consumableitems_id)
				WHERE
					$consumableTable.itemtype = 'User'
					AND $consumableTable.items_id = '$ID'";

			$result = $DB->query($query);
			$type_name = $item->getTypeName();

			if ($DB->numrows($result) > 0) {
				while ($data = $DB->fetch_assoc($result)) {
					$cansee = $item->can($data["id"], READ);
					$link   = $data["name"];

					if ($cansee) {
						$link_item = 'consumableitem.form.php';

						if ($_SESSION["glpiis_ids_visible"] || empty($link)) {
							$link = sprintf(__('%1$s (%2$s)'), $link, $data["id"]);
						}

						$link = "<a href='".$link_item."?id=".$data["id"]."'>".$link."</a>";
					}

					echo "<tr class='tab_bg_1'><td class='center'>$type_name</td>";
					echo "<td class='center'>".Dropdown::getDropdownName("glpi_entities", $data["entities_id"])."</td>";
					echo "<td class='center'>$link</td>";
					echo "<td class='center'>".$data["date_in"]."</td>";
					echo "<td class='center'>".$data["date_out"]."</td>";
					echo "<td class='center'>".Consumable::getStatus($data["id"])."</td></tr>";
				}
			}  else {
				echo "<tr class='tab_bg_1'><td class='center' colspan='6'>".__('No results.')."</td></tr>";
			}
		}

		echo "</table></div>";

		return true;
	}

}