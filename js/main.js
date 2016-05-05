"use strict";
var prefs = {};

prefs.toggleMore = function (target) {
    return target.classList.toggle("is-open");
};

prefs.toggleCheckboxes = function (type, list) {
    var inputs = list.querySelectorAll('input');
    Array.prototype.map.call(inputs, function (input) {
        input.checked = type === 'all';
    });
};

prefs.addMoreLink = function (list) {
    var el = document.createElement("a");
    el.href = "#";
    el.className = "trigger-more pref-more";
    el.innerText = "+ More";

    el.addEventListener("click", function (e) {
        e.preventDefault();
        prefs.toggleMore(e.target.parentElement);
    });
    list.appendChild(el);
    list.classList.add("has-more");
};

prefs.addMultiSelects = function (label, list) {
    if (label.getElementsByClassName("pref-multiselect").length < 1) {
        var options = ["all", "none"];
        var container = document.createElement("div");
        container.className = "pref-multiselect";

        options.map(function (type) {
            var el = document.createElement("a");
            el.href = "#";
            el.innerText = type;

            el.addEventListener("click", function (e) {
                e.preventDefault();
                prefs.toggleCheckboxes(type, list);
            });

            container.appendChild(el);
        });
        label.appendChild(container);
    }
};

prefs.watchTextInputChanges = function () {
    var textInputs = document.querySelectorAll(".input-text");
    function isDirty(input) {
        if (input.value) {
            input.offsetParent.classList.add("dirty");
        } else {
            input.offsetParent.classList.remove("dirty");
        }
    }
    Array.prototype.map.call(textInputs, function (input) {
        isDirty(input);
        input.onfocus = function () {
            input.offsetParent.classList.add("focused");
        };
        input.onblur = function () {
            input.offsetParent.classList.remove("focused");
        };
        input.addEventListener("keyup", function (e) {
            isDirty(e.target);
        });
    });
};

prefs.init = function () {
    var prefSection = document.getElementsByClassName("pref-section");
    Array.prototype.map.call(prefSection, function (section) {
        var label = section.getElementsByClassName("pref-label--container")[0];
        var list = section.getElementsByClassName("pref-list--container")[0];
        if (label) {
            prefs.addMultiSelects(label, list);
        }
        if (list.firstElementChild.childElementCount > 8) {
            prefs.addMoreLink(list);
        }
    });
    prefs.watchTextInputChanges();
};

(function () {
    document.documentElement.className = "js";
    prefs.init();
}());
