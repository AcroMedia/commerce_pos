/*!
 * Key navigator plugin for jQuery / Zepto.
 *
 * https://github.com/nekman/keynavigator
 */

(function(root, factory) {

    // CommonJS.
    if (typeof exports === 'object') {
        module.exports = factory(require('jquery'));
    } else if (typeof root.define === 'function' && root.define.amd) {
        // AMD.
        // jQuery 1.7+ registers it self as a AMD module.
        // If Zepto is used, define jquery and return Zepto eg:
        //
        //    define('jquery', function() {
        //      return jQuery;
        //    });
        //
        define(['jquery'], factory);
    } else {
        // Assume jQuery or Zepto are loaded from <script> tags.
        factory(root.jQuery || root.Zepto);
    }
}(this, function($) {



    /*
     * Utility for converting a jQuery position to a {cell} object
     */
    var CellFactory = {
        createFrom: function($el) {
            var position = $el.position();

            return {
                pos: {
                    left: Math.round(position.left),
                    top: Math.round(position.top)
                },

                $el: $el
            };
        }
    };


    /*
     * CellTable
     *  - Finds and navigates in cells.
     *
     * @param $nodes - jQuery nodes to build the cell table from.
     *
     * TODO: Refactor!
     */
    var CellTable = function($nodes) {
        this.table = this.buildTable($nodes);
        this.rows = this.buildRows();
        this.columns = this.buildColumns();
    };

    CellTable.prototype = {
        buildTable: function($nodes) {
            return $nodes.map(function() {
                return CellFactory.createFrom($(this));
            });
        },

        buildColumns: function() {
            var columns = {},
                self = this;

            $.each(this.table, function(index, cell) {
                columns[cell.pos.left] = self.getColumnElements(cell);
            });

            return columns;
        },

        buildRows: function() {
            var rows = {},
                self = this;

            $.each(this.table, function(i, cell) {
                rows[cell.pos.top] = self.getRowElements(cell);
            });

            return rows;
        },

        getRowElements: function(compareCell) {
            var self = this;

            return $.map(this.table, function(cell) {
                if (self.isSameRow(cell, compareCell)) {
                    return cell;
                }

                return null;
            });
        },

        getColumnElements: function(compareCell) {
            var self = this;

            return $.map(this.table, function(cell) {
                if (self.isSameColumn(cell, compareCell)) {
                    return cell;
                }

                return null;
            });
        },

        getCurrent: function($el) {
            var cell = CellFactory.createFrom($el);

            return this.findPosition(
                this.getCell(cell)
            );
        },

        isSameColumn: function(cell, compareCell) {
            if (!compareCell) {
                throw 'cell';
            }

            return cell.pos.left === compareCell.pos.left;
        },

        isSameRow: function(cell, compareCell) {
            return cell.pos.top === compareCell.pos.top;
        },

        isSame: function(cell, compareCell) {
            return this.isSameColumn(cell, compareCell) && this.isSameRow(cell, compareCell);
        },

        getCell: function(cell) {
            var self = this;
            return $.map(this.table, function(compareCell) {
                if (self.isSame(cell, compareCell)) {
                    return compareCell;
                }

                return null;
            })[0];
        },

        findIndex: function(array, callback) {
            var index = 0,
                len = array.length;

            for (; index < len; index++) {
                if (callback(array[index])) {
                    return index;
                }
            }

            return index;
        },

        findPosition: function(cell) {
            var colCells = this.getColumnElements(cell),
                rowCells = this.getRowElements(cell),

                rowIndex = this.findIndex(colCells, function(colCell) {
                    return colCell.pos.top == cell.pos.top;
                }),

                colIndex = this.findIndex(rowCells, function(rowCell) {
                    return rowCell.pos.left == cell.pos.left;
                });

            return {
                colIndex: colIndex,
                rowIndex: rowIndex
            };
        }
    };

    var keyMappings = {
        0: '?',
        8: 'backspace',
        9: 'tab',
        13: 'enter',
        16: 'shift',
        17: 'ctrl',
        18: 'alt',
        19: 'pause_break',
        20: 'caps_lock',
        27: 'escape',
        33: 'page_up',
        34: 'page_down',
        35: 'end',
        36: 'home',
        37: 'left_arrow',
        38: 'up_arrow',
        39: 'right_arrow',
        40: 'down_arrow',
        45: 'insert',
        46: 'delete',
        48: '0',
        49: '1',
        50: '2',
        51: '3',
        52: '4',
        53: '5',
        54: '6',
        55: '7',
        56: '8',
        57: '9',
        65: 'a',
        66: 'b',
        67: 'c',
        68: 'd',
        69: 'e',
        70: 'f',
        71: 'g',
        72: 'h',
        73: 'i',
        74: 'j',
        75: 'k',
        76: 'l',
        77: 'm',
        78: 'n',
        79: 'o',
        80: 'p',
        81: 'q',
        82: 'r',
        83: 's',
        84: 't',
        85: 'u',
        86: 'v',
        87: 'w',
        88: 'x',
        89: 'y',
        90: 'z',
        91: 'left_window_key',
        92: 'right_window_key',
        93: 'select_key',
        96: 'numpad_0',
        97: 'numpad_1',
        98: 'numpad_2',
        99: 'numpad_3',
        100: 'numpad 4',
        101: 'numpad_5',
        102: 'numpad_6',
        103: 'numpad_7',
        104: 'numpad_8',
        105: 'numpad_9',
        106: 'multiply',
        107: 'add',
        109: 'subtract',
        110: 'decimal point',
        111: 'divide',
        112: 'f1',
        113: 'f2',
        114: 'f3',
        115: 'f4',
        116: 'f5',
        117: 'f6',
        118: 'f7',
        119: 'f8',
        120: 'f9',
        121: 'f10',
        122: 'f11',
        123: 'f12',
        144: 'num_lock',
        145: 'scroll_lock',
        186: ';',
        187: '=',
        188: ',',
        189: 'dash',
        190: '.',
        191: '/',
        192: 'grave_accent',
        219: 'open_bracket',
        220: '\\',
        221: 'close_braket',
        222: 'single_quote'
    };


    /*
     * @param $nodes - jQuery nodes.
     * @param settings - Optional settings.
     */
    var KeyNavigator = function($nodes, settings)  {
        // Extend custom settings with default settings.
        // Could 'deep copy' ($.extend(true, ...)) the entire settings, but this could result
        // in conflicts betweeen methods provided by KeyNavigator and methods provided
        // by the user.
        var options = settings || {};
        this.options = $.extend({}, this.defaults, options);
        this.options.keys = $.extend({}, this.defaults.keys, options.keys);

        this.$nodes = $nodes;
        this.$parent =
            this.options.parent ?
                $(this.options.parent) : $nodes.parent();

        if (this.options.removeOutline) {
            this.$parent.css({ outline: 'none' });
        }

        // If the parent node doesn't have a tabindex attribute, then add one.
        // This is needed to be able to set focus on the node.
        if (!this.$parent.attr('tabindex')) {
            this.$parent.attr({ tabindex: this.options.tabindex || -1 });
        }
    };

    KeyNavigator.keys = keyMappings;

    KeyNavigator.prototype = {
        // Default settings
        defaults: {
            useCache: true,
            cycle: true,
            activateOn: 'click',
            parentFocusOn: 'click',
            activeClass: 'active',
            removeOutline: true,
            // Default keys.
            keys: {
                up_arrow: 'up',
                down_arrow: 'down',
                left_arrow: 'left',
                right_arrow: 'right'
            },
            onBeforeActive: $.noop,
            onAfterActive: $.noop
        },

        move: function(info) {
            var cells = info.cells[info.cellPosition],
                cell = cells[info.index];

            if (!cell && this.options.cycle) {
                cell = cells[info.firstIndex ? 0 : cells.length - 1];
            }

            if (!cell) {
                return;
            }

            this.setActive(cell.$el);
        },

        down: function($el, cellIndex) {
            $el.trigger('down', [$el]);

            var colCells = this.cellTable.columns;

            this.move({
                cellPosition: CellFactory.createFrom($el).pos.left,
                index: cellIndex.rowIndex + 1,
                cells: colCells,
                firstIndex: true
            });
        },

        up: function($el, cellIndex) {
            $el.trigger('up', [$el]);

            var colCells = this.cellTable.columns;

            this.move({
                cellPosition: CellFactory.createFrom($el).pos.left,
                index: cellIndex.rowIndex - 1,
                cells: colCells
            });
        },

        left: function($el, cellIndex) {
            $el.trigger('left', [$el]);

            var rowCells = this.cellTable.rows;

            this.move({
                cellPosition: CellFactory.createFrom($el).pos.top,
                index: cellIndex.colIndex - 1,
                cells: rowCells
            });
        },

        right: function($el, cellIndex) {
            $el.trigger('right', [$el]);

            var rowCells = this.cellTable.rows;

            this.move({
                cellPosition: CellFactory.createFrom($el).pos.top,
                index: cellIndex.colIndex + 1,
                cells: rowCells,
                firstIndex: true
            });
        },

        findCell: function($selected) {
            try {
                return this.cellTable.getCurrent($selected);
            } catch (ex) {
                // Nothing to do.
            }

            // Could not find any cell. Try to rebuild the CellTable and try again...
            this.reBuild();

            return this.cellTable.getCurrent($selected);
        },

        handleKeyDown: function(e) {
            // Use event.which property to normalizes event.keyCode and event.charCode.
            var fn = this.options.keys[KeyNavigator.keys[e.which]] || this.options.keys[e.which];

            if (!fn) {
                // No handler found for current keyCode.
                return;
            }

            //IE: http://stackoverflow.com/questions/1000597/event-preventdefault-function-not-working-in-ie
            e.preventDefault ? e.preventDefault() : e.returnValue = false;

            // If 'useCache' isn't enabled,
            // then query for DOM-nodes with the same selector.
            if (!this.cellTable || !this.options.useCache) {
                this.reBuild();
            }

            var $selected = this.$parent.find('.' + this.options.activeClass);
            if (!$selected.length) {
                // One more try...
                $selected = this.$nodes.first();
            }

            if (!$selected.length) {
                // Could not find any element.
                return;
            }

            var cell = this.findCell($selected),
            //TODO: Should be fixed.
                navigationHandle = this[fn];

            if (navigationHandle) {
                return navigationHandle.apply(this, [$selected, cell, e]);
            }

            fn.apply(this, [$selected, cell, e]);
        },

        onBeforeActive: function($el) {
            return this.options.onBeforeActive.apply(this, [$el]);
        },

        onAfterActive: function($el) {
            return this.options.onAfterActive.apply(this, [$el]);
        },

        setActive: function($el) {
            var result = this.onBeforeActive($el);
            // Remove the active class (from all nodes),
            // add the active class to the selected node.
            if (result !== false) {
                this.$nodes.removeClass(this.options.activeClass);
                $el.addClass(this.options.activeClass);
            }

            this.onAfterActive($el);
        },

        reBuild: function() {

            // Unbind the events before bind.
            var $parent = this.$parent,
                self = this;


            // If 'useCache' isn't enabled,
            // then query for DOM-nodes with the same selector.
            if (!this.options.useCache) {
                this.$nodes = $(this.$nodes.selector);
            }

            $parent
                .off('keydown')
                .off(this.options.parentFocusOn)
                .on('keydown', $.proxy(this.handleKeyDown, this))
                .on(this.options.parentFocusOn, function() {
                    $parent.focus();
                });


            // Get all "non watched" nodes (thoose without the attribute keynavigator-watched).
            // And apply the "activateOn" event.
            // This is to avoid duplicate events if new nodes appears in the
            // DOM.
            var $noneWatchedNodes = this.$nodes.filter(function() {
                return !$(this).attr('keynavigator-watched');
            });

            $noneWatchedNodes
                .attr('keynavigator-watched', true)
                .on(this.options.activateOn, function() {
                    self.setActive($(this));
                });


            this.cellTable = new CellTable(this.$nodes);
        }
    };

    $.fn.keynavigator = function(options) {
        var keynavigator = new KeyNavigator(this, options);

        // Need to wait until resizing  is done, so that we don't
        // rebuilding the cellTable more times than we need to.
        var resizing;
        $(window).on('resize', function() {
            clearTimeout(resizing);
            resizing = setTimeout(function() {
                keynavigator.reBuild();
            }, 200);
        });

        keynavigator.reBuild();

        // Return a extended jQuery node with
        // a 'keynavigator' property that points to the 'KeyNavigator' instance.
        return $.extend(this, {
            keynavigator: keynavigator
        });
    };
    // Return the $-function
    return $;

}));