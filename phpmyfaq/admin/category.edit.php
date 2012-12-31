<?php
/**
 * Edits a category
 * 
 * PHP Version 5.2
 * 
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2003-03-10
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission['editcateg']) {

    $id              = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);
    $category        = new PMF_Category($current_admin_user, $current_admin_groups, false);
    $categories      = $category->getAllCategories();
    $user_permission = $category->getPermissions('user', array($id));

    if ($user_permission[0] == -1) {
        $all_users        = true;
        $restricted_users = false;
    } else {
        $all_users        = false;
        $restricted_users = true;
    }

    $group_permission = $category->getPermissions('group', array($id));
    if ($group_permission[0] == -1) {
        $all_groups        = true;
        $restricted_groups = false;
    } else {
        $all_groups        = false;
        $restricted_groups = true;
    }

?>

        <header>
            <h2><?php print $PMF_LANG['ad_categ_edit_1'] . ' ' . $categories[$id]['name'] . ' ' . $PMF_LANG['ad_categ_edit_2']; ?></h2>
        </header>

        <form action="?action=updatecategory" method="post">
            <input type="hidden" name="id" value="<?php print $id; ?>" />
            <input type="hidden" id="catlang" name="catlang" value="<?php print $categories[$id]['lang']; ?>" />
            <input type="hidden" name="parent_id" value="<?php print $categories[$id]['parent_id']; ?>" />
            <input type="hidden" name="csrf" value="<?php print $user->getCsrfTokenFromSession(); ?>" />

            <p>
                <label><?php print $PMF_LANG['ad_categ_titel']; ?>:</label>
                <input type="text" id="name" name="name" size="30" style="width: 300px;"
                       value="<?php print $categories[$id]['name']; ?>" autofocus="autofocus" />
            </p>

            <p>
                <label><?php print $PMF_LANG['ad_categ_desc']; ?>:</label>
                <textarea id="description" name="description" rows="3" cols="80"
                          style="width: 300px;"><?php print $categories[$id]['description']; ?></textarea>
            </p>

            <p>
                <label><?php print $PMF_LANG['ad_categ_owner']; ?>:</label>
                <select name="user_id" size="1">
                <?php print $user->getAllUserOptions($categories[$id]['user_id']); ?>
                </select>
            </p>
<?php
    if ($faqconfig->get('security.permLevel') != 'basic') {
?>
            <p>
                <label><?php print $PMF_LANG['ad_entry_grouppermission']; ?></label>
                <input type="radio" name="grouppermission" class="active" value="all" <?php print ($all_groups ? 'checked="checked"' : ''); ?>/> <?php print $PMF_LANG['ad_entry_all_groups']; ?>
                <input type="radio" name="grouppermission" class="active" value="restricted" <?php print ($restricted_groups ? 'checked="checked"' : ''); ?>/> <?php print $PMF_LANG['ad_entry_restricted_groups']; ?>
                <select name="restricted_groups" size="1">
                    <?php print $user->perm->getAllGroupsOptions($group_permission[0]); ?>
                </select>
            </p>
<?php
    } else {
?>
            <input type="hidden" name="grouppermission" class="active" value="all" />
<?php 
    }
?>
            <p>
                <label><?php print $PMF_LANG['ad_entry_userpermission']; ?></label>
                <input type="radio" name="userpermission" class="active" value="all" <?php print ($all_users ? 'checked="checked"' : ''); ?>/> <?php print $PMF_LANG['ad_entry_all_users']; ?>
                <input type="radio" name="userpermission" class="active" value="restricted" <?php print ($restricted_users ? 'checked="checked"' : ''); ?>/> <?php print $PMF_LANG['ad_entry_restricted_users']; ?>
                <select name="restricted_users" size="1">
                    <?php print $user->getAllUserOptions($user_permission[0]); ?>
                </select>
            </p>

<?php
    if ($faqconfig->get('main.enableGoogleTranslation') === true) {
?>
            <header>
                <h3><?php print $PMF_LANG["ad_menu_translations"]; ?></h3>
            </header>
            <div id="editTranslations">
            <?php
            if ($faqconfig->get('main.googleTranslationKey') == '') {
                print $PMF_LANG["msgNoGoogleApiKeyFound"];
            } else {
            ?>
            <p>
                <label for="langTo"><?php print $PMF_LANG["ad_entry_locale"]; ?>:</label>
                <?php print PMF_Language::selectLanguages($faqData['lang'], false, array(), 'langTo'); ?>
            </p>
            <input type="hidden" name="used_translated_languages" id="used_translated_languages" value="" />
            <div id="getedTranslations">
            </div>
            <?php } ?>
        </div>
    </fieldset>        
<?php
    }
?>
            <p>
                <input class="submit" type="submit" name="submit" value="<?php print $PMF_LANG['ad_categ_updatecateg']; ?>" />
            </p>
    </form>
<?php 
    if ($faqconfig->get('main.enableGoogleTranslation') === true) {
?>        
    <script src="https://www.google.com/jsapi?key=<?php echo $faqconfig->get('main.googleTranslationKey')?>" type="text/javascript"></script>
    <script type="text/javascript">
    /* <![CDATA[ */
    google.load("language", "1");

    var langFromSelect = $("#catlang");
    var langToSelect   = $("#langTo");       
    
    $("#langTo").val($("#catlang").val());
        
    // Add a onChange to the translation select
    langToSelect.change(
        function() {
            var langTo = $(this).val();

            if (!document.getElementById('name_translated_' + langTo)) {

                // Add language value
                var languages = $('#used_translated_languages').val();
                if (languages == '') {
                    $('#used_translated_languages').val(langTo);
                } else {
                    $('#used_translated_languages').val(languages + ',' + langTo);
                }
               
                var fieldset = $('<fieldset></fieldset>')
                    .append($('<legend></legend>').html($("#langTo option:selected").text()));

                // Text for title
                fieldset
                    .append('<p>' +
                            '<label for="name_translated_' + langTo + '">' +
                            '<?php print $PMF_LANG["ad_categ_titel"]; ?>:' +
                            '</label>' +
                            '<input type="text" id="name_translated_' + langTo + '" name="name_translated_' + langTo + '" maxlength="255" style="width: 300px;">' +
                            '</p>');

                // Textarea for description
                fieldset
                    .append('<p>' +
                            '<label for="description_translated_' + langTo + '">' +
                            '<?php print $PMF_LANG["ad_categ_desc"]; ?>:' +
                            '</label>' +
                            '<textarea id="description_translated_' + langTo + '" name="description_translated_' + langTo + '" cols="80" rows="3" style="width: 300px;"></textarea>' +
                            '</p>');

                $('#getedTranslations').append(fieldset);
            }

            // Set the translated text
            var langFrom = $('#catlang').val();
            getGoogleTranslation('#name_translated_' + langTo, $('#name').val(), langFrom, langTo);
            getGoogleTranslation('#description_translated_' + langTo, $('#description').val(), langFrom, langTo);
        }
    );
    /* ]]> */
    </script>
<?php
    }
} else {
    print $PMF_LANG['err_NotAuth'];
}
