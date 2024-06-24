# MakePdfBook

An extension for MediaWiki to produce a PDF book from a category.

Need to write up:

- What the files do
- The available magic words for titlepages
- The caveats around special characters
- The automagic around automatic titlepage inclusion and draft marking


## Developing
- Install docker.
- Go to `dev/`.
- Run `docker compose pull` to ensure you have an up to date copy of the container images.
- Run `docker compose up`.
- Edit away; the `docker-compose.yaml` hot-mounts this directory as the MakePdfBook extension in the MediaWiki container, so any changes you make here will be instantly reflected in the container's behaviour.

TODO: write notes somewhere about debugging MakePdfBook behaviour - specifically about jumping into the mediawiki container and going spelunking in the temp files generated into `/tmp/MakePdfBook/Test`

## Acknowledgements
This was inspired by, and springboarded off, the work of **Aran Dunkley** on the [MediaWiki PdfBook extension](https://www.mediawiki.org/wiki/Extension:PdfBook).