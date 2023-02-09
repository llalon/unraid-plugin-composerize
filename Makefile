ROOT_DIR:=$(shell dirname $(realpath $(firstword $(MAKEFILE_LIST))))
VERSION:=$(shell date '+%Y.%m.%d')
BRANCH:=$(shell git branch --show-current)

.PHONY: all build build-deps clean clean-all

build-all: build-deps build

clean-all: clean clean-deps

build:
	bash $(ROOT_DIR)/build/build.sh $(VERSION) $(BRANCH)
	cp $(ROOT_DIR)/archive/composerize-$(BRANCH)-$(VERSION).txz composerize-$(BRANCH)-latest.txz

clean-deps:
	rm $(ROOT_DIR)/source/composerize/usr/local/emhttp/plugins/composerize/bin/composerize

build-deps:
	bash $(ROOT_DIR)/build/build-deps.sh

clean:
	rm $(ROOT_DIR)/*.txz