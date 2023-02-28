/**
 * CAO-Schnittstelle
 *
 * @link https://github.com/agroviva/cao
 * @package cao
 * @author Enver Morinaj
 */

// conditional import AppJS for 23.1+, but not for non-module include of 21.1 and before, as an import gives a JS syntax error if not in module context
//import {AppJS} from "../../api/js/jsapi/app_base.js";
(typeof window.$LAB === 'undefined' ? import("../../api/js/jsapi/app_base.js") : Promise.resolve({AppJS: window.AppJS})).then((modules) => {
    /**
     * Javascript for cao
     *
     * @augments AppJS
     */
    app.classes.cao = modules.AppJS.extend(
    {
        /**
         * application name
         */
        appname: 'cao',
    
        /**
         * Constructor
         *
         * @memberOf app.calendar
         */
        init: function()
        {
            // call parent
            this._super.apply(this, arguments);
    
            jQuery(document).ready(function()
            {
                // add target _blank to all external links, as our content security policy will prevent them otherwise
                jQuery('a').click(function()
                {
                    if (this.href.substr(0, 1+window.location.origin.length) !== window.location.origin+'/')
                    {
                        this.target = '_blank';
                    }
                });
            });
        },
    
        /**
         * Destructor
         */
        destroy: function()
        {
            // call parent
            this._super.apply(this, arguments);
        },
    
        /**
         * This function is called when the etemplate2 object is loaded
         * and ready.  If you must store a reference to the et2 object,
         * make sure to clean it up in destroy().
         *
         * @param {etemplate2} _et2 newly ready et2 object
         * @param {string} _name name of template
         */
        et2_ready: function(_et2, _name)
        {
            // call parent
            this._super.apply(this, arguments);
        },
    
        /**
         * Onchange for readable and writable acl: default back to "Everyone" if non set
         *
         * @param {DOMNode} _node
         * @param {et2_select} _widget
         */
        onchange_acl: function(_node, _widget)
        {
            var value = _widget.get_value();
            if (jQuery.isArray(value) && !value.length)
            {
                _widget.set_value('_0');
            }
        }
    });
    
    }); // conditional import AppJS