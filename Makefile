ROOT_DIR:=$(shell dirname $(realpath $(firstword $(MAKEFILE_LIST))))
VERSION ?= ""

.PHONY: all build build-deps clean

build-all: build build-deps

clean-all: clean clean-deps

build:
	bash $(ROOT_DIR)/build/build.sh $(VERSION)

clean-deps:
	rm $(ROOT_DIR)/source/composerize/usr/local/emhttp/plugins/composerize/bin/composerize

build-deps:
	bash $(ROOT_DIR)/build/build-deps.sh

clean:
	rm $(ROOT_DIR)/*.txz

