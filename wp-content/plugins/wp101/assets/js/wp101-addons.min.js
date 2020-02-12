/*! WP101 - v */
!function(n){"use strict";document.addEventListener("click",function(t){"BUTTON"===t.target.tagName&&t.target.classList.contains("notice-dismiss")&&n.post(ajaxurl,{action:"wp101_dismiss_notice",addons:t.target.parentElement.dataset.wp101AddonSlug.split(","),nonce:wp101Addons.nonce})})}(jQuery);
//# sourceMappingURL=wp101-addons.min.js.map