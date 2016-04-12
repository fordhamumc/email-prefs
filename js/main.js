function toggleMore(target) {
    if (target.classList.contains("is-open")) {
        return target.classList.remove("is-open")
    }
    target.classList.add("is-open");
}

(function () {
    document.documentElement.className = "js";

    var prefLists = document.getElementsByClassName("pref-list--container");
    Array.prototype.map.call(prefLists, function (list) {
        if (list.firstElementChild.childElementCount > 8) {

            var el = document.createElement("a");
                el.href = "#";
                el.className = "trigger-more pref-more";
                el.innerText = "+ More";

            el.addEventListener("click", function(e) {
                e.preventDefault();
                toggleMore(e.target.parentElement);
            });

            list.appendChild(el);
            list.classList.add("has-more");
        }
    });
})();
