# HANDMATIG INSTALLEREN

Normaal werkt de plugin zichzelf bij vanuit de GitHub-releases (zie README, Updates).
Installeer hem met de hand bij de eerste installatie, of als een site een nieuwe versie
niet vanzelf binnenhaalt. Je hebt alleen een beheerdersaccount in wp-admin nodig; SSH of
FTP is niet nodig.

Werk eerst de testomgeving bij en controleer die, daarna de live site.

## 1. DOWNLOAD DE ZIP

1. Open de [laatste release](https://github.com/swbase/yga-wordpress-plugin/releases/latest).
2. Download onder **Assets** het bestand **`yga-wordpress-plugin.zip`**.

Neem niet "Source code (zip)": die zip bevat de tests en een map met het versienummer
in de naam, en WordPress installeert hem als een tweede, losse plugin.

Een oudere versie staat onder [alle releases](https://github.com/swbase/yga-wordpress-plugin/releases).

## 2. UPLOAD IN WORDPRESS

1. Ga in wp-admin naar **Plugins → Nieuwe plugin** en klik bovenaan op
   **Plugin uploaden**. Of open direct `/wp-admin/plugin-install.php?tab=upload`.
2. Kies `yga-wordpress-plugin.zip` en klik op **Nu installeren**.
3. Staat de plugin al op de site, dan meldt WordPress **"Deze plugin is al
   geïnstalleerd."** en zet het de huidige en de geüploade versie naast elkaar. Klik op
   **Huidige vervangen door de geüploade**.
4. Bij een eerste installatie: klik op **Plugin activeren**. Een vervangen plugin blijft
   actief.

## 3. CONTROLEER HET RESULTAAT

- **Plugins:** "Yielder – Stuurlui site extensions" staat op het nieuwe versienummer en
  is actief.
- **REST API:** vanaf 0.2.1 heeft het `knowledge_hub`-endpoint het veld `yga`:

  ```bash
  curl -s -X OPTIONS https://yielder.com/wp-json/wp/v2/knowledge_hub \
    | jq '.schema.properties | has("yga")'   # true
  ```

## EERSTE INSTALLATIE NA 0.1.0

Versie 0.1.0 heette `stuurlui-wp-plugin`. Deactiveer en verwijder die eerst onder
**Plugins**, en installeer daarna `yga-wordpress-plugin.zip` zoals hierboven. Tussen het
verwijderen en het activeren kan geen artikel via de REST API gevuld worden; doe het
dus achter elkaar.

## TERUG NAAR EEN EERDERE VERSIE

Download de zip van die versie onder [alle releases](https://github.com/swbase/yga-wordpress-plugin/releases)
en upload hem zoals in stap 2. WordPress waarschuwt dat de geüploade versie ouder is;
kies toch **Huidige vervangen door de geüploade**. Let op: zolang een nieuwere release
bestaat en automatische updates werken, werkt de site zichzelf binnen een dag weer bij.

## ALS HET MISGAAT

- **"Er zijn geen geldige plugins gevonden."** Je uploadde een ander bestand dan
  `yga-wordpress-plugin.zip`, bijvoorbeeld `manifest.json` of een uitgepakte en opnieuw
  ingepakte map. Download de zip opnieuw en upload hem zonder hem te openen.
- **Twee keer "Yielder – Stuurlui site extensions" in de lijst.** Er is een tweede kopie
  geïnstalleerd, meestal uit "Source code (zip)". Laat de actieve staan, verwijder de
  inactieve, en installeer daarna opnieuw volgens stap 1 en 2. Activeer de tweede kopie
  niet: twee kopieën tegelijk geven een fatale fout.
- **Geen knop "Plugin uploaden" of "Nieuwe plugin".** Je account is geen beheerder, of de
  site staat bestandswijzigingen niet toe (`DISALLOW_FILE_MODS`). Vraag dan de beheerder
  van de hosting.
