#---------------------------
# This script generates a new pmpro-roles.pot file for use in translations.
# To generate a new pmpro-roles.pot, cd to the main /pmpro-roles/ directory,
# then execute `languages/gettext.sh` from the command line.
# then fix the header info (helps to have the old pmpro.pot open before running script above)
# then execute `cp languages/pmpro-roles.pot languages/pmpro-roles.po` to copy the .pot to .po
# then execute `msgfmt languages/pmpro-roles.po --output-file languages/pmpro-roles.mo` to generate the .mo
#---------------------------
echo "Updating pmpro-roles.pot... "
xgettext -j -o languages/pmpro-roles.pot \
--default-domain=pmpro-roles \
--language=PHP \
--keyword=_ \
--keyword=__ \
--keyword=_e \
--keyword=_ex \
--keyword=_n \
--keyword=_x \
--keyword=esc_html_e \
--keyword=esc_html__ \
--sort-by-file \
--package-version=1.0 \
--msgid-bugs-address="info@paidmembershipspro.com" \
$(find . -name "*.php")
echo "Done!"