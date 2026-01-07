<script>
import { Link, router } from '@inertiajs/vue3';
import { layoutComputed } from "@/state/helpers";
import simplebar from "simplebar-vue";
import { mapGetters } from 'vuex';

export default {
  components: {
    simplebar,
    Link
  },
  props: ['activeMenu','supportTicket'],
  data() {
    return {
      settings: {
        minScrollbarLength: 60,
      },
     
    };
  },
  computed: {
    ...layoutComputed,
    ...mapGetters(['permissions']),
    layoutType: {
      get() {
        return this.$store ? this.$store.state.layout.layoutType : {} || {};
      },
    },
  },
  mounted() {
    this.initActiveMenu();
    this.onRoutechange();
    if (document.querySelectorAll(".navbar-nav .collapse")) {
      let collapses = document.querySelectorAll(".navbar-nav .collapse");

      collapses.forEach((collapse) => {
        // Hide sibling collapses on `show.bs.collapse`
        collapse.addEventListener("show.bs.collapse", (e) => {
          e.stopPropagation();
          let closestCollapse = collapse.parentElement.closest(".collapse");
          if (closestCollapse) {
            let siblingCollapses =
              closestCollapse.querySelectorAll(".collapse");
            siblingCollapses.forEach((siblingCollapse) => {
              if (siblingCollapse.classList.contains("show")) {
                siblingCollapse.classList.remove("show");
                siblingCollapse.parentElement.firstChild.setAttribute("aria-expanded", "false");
              }
            });
          } else {
            let getSiblings = (elem) => {
              // Setup siblings array and get the first sibling
              let siblings = [];
              let sibling = elem.parentNode.firstChild;
              // Loop through each sibling and push to the array
              while (sibling) {
                if (sibling.nodeType === 1 && sibling !== elem) {
                  siblings.push(sibling);
                }
                sibling = sibling.nextSibling;
              }
              return siblings;
            };
            let siblings = getSiblings(collapse.parentElement);
            siblings.forEach((item) => {
              if (item.childNodes.length > 2) {
                item.firstElementChild.setAttribute("aria-expanded", "false");
                item.firstElementChild.classList.remove("active");
              }
              let ids = item.querySelectorAll("*[id]");
              ids.forEach((item1) => {
                item1.classList.remove("show");
                item1.parentElement.firstChild.setAttribute("aria-expanded", "false");
                item1.parentElement.firstChild.classList.remove("active");
                if (item1.childNodes.length > 2) {
                  let val = item1.querySelectorAll("ul li a");

                  val.forEach((subitem) => {
                    if (subitem.hasAttribute("aria-expanded"))
                      subitem.setAttribute("aria-expanded", "false");
                  });
                }
              });
            });
          }
        });

        // Hide nested collapses on `hide.bs.collapse`
        collapse.addEventListener("hide.bs.collapse", (e) => {
          e.stopPropagation();
          let childCollapses = collapse.querySelectorAll(".collapse");
          childCollapses.forEach((childCollapse) => {
            let childCollapseInstance = childCollapse;
            childCollapseInstance.classList.remove("show");
            childCollapseInstance.parentElement.firstChild.setAttribute("aria-expanded", "false");
          });
        });
      });
    }
  },

  methods: {
    onRoutechange() {
      // this.initActiveMenu();
      setTimeout(() => {
        var currentPath = window.location.pathname;
        if (document.querySelector("#navbar-nav")) {
          let currentPosition = document.querySelector("#navbar-nav").querySelector('[href="' + currentPath + '"]')?.offsetTop;
          if (currentPosition > document.documentElement.clientHeight) {
            document.querySelector("#scrollbar .simplebar-content-wrapper") ? document.querySelector("#scrollbar .simplebar-content-wrapper").scrollTop = currentPosition + 300 : '';
          }
        }
      }, 500);
    },

    isSubMenuActive(submenuPaths) {
      // Check if the current URL starts with any of the submenu paths
      return submenuPaths.some((path) => window.location.pathname.startsWith('/' + path));
    },



  initActiveMenu() {
    const intervalId = setInterval(() => {
      const currentPath = window.location.pathname;
      const menuLinks = document.querySelectorAll(".navbar-nav .nav-link");
      if (menuLinks.length > 0) {
        clearInterval(intervalId); // Stop the interval once menu links are found
        // Iterate over the menu links and apply the "active" class
        menuLinks.forEach(link => {
          const linkHref = link.getAttribute("href");
          if (currentPath.startsWith(linkHref)) {
            link.classList.add("active");
            let parentCollapseDiv = link.closest(".collapse.menu-dropdown");
            if (parentCollapseDiv) {
              parentCollapseDiv.classList.add("show");
              parentCollapseDiv.parentElement.children[0].classList.add("active");
              parentCollapseDiv.parentElement.children[0].setAttribute("aria-expanded", "true");
             let ancestorCollapse = parentCollapseDiv.parentElement.closest(".collapse.menu-dropdown");
              while (ancestorCollapse) {
                ancestorCollapse.classList.add("show");
                const previousSibling = ancestorCollapse.previousElementSibling;
                if (previousSibling) {
                  previousSibling.classList.add("active");
                }
                ancestorCollapse = ancestorCollapse.closest(".collapse.menu-dropdown")?.previousElementSibling;
              }
            }
          }
        });
      }
    }, 100); // Check every 100ms
  }
  },
};
</script>

<template>
  <BContainer fluid>
    <div id="two-column-menu"></div>

    <template v-if="layoutType === 'vertical' || layoutType === 'semibox'">
      <ul class="navbar-nav h-100 mt-5" id="navbar-nav">
        <div  class="menu">
          <!-- <li class="menu-title">
            <span data-key="t-home"> {{ $t("dispatch") }}</span>
          </li> -->
          <li class="nav-item mt-4">
            <Link class="nav-link menu-link" href="/dashboard">
                <i class=" ri-home-4-line"></i> <span data-key="t-dashboard">{{ $t("dashboard") }}</span>
            </Link>
          </li>
          <li class="nav-item mt-4">
            <Link class="nav-link menu-link" href="/dispatcher/bookride">
                <i class="ri-map-2-line"></i> <span data-key="t-dashboard">{{ $t("bookings") }}</span>
            </Link>
          </li>
          <li class="nav-item mt-4">
            <Link class="nav-link menu-link" href="/dispatcher/godeye">
                <i class="ri-user-3-fill"></i> <span data-key="t-dashboard">{{ $t("drivers") }}</span>
            </Link>
          </li>
          <li class="nav-item mt-4">
            <Link class="nav-link menu-link" href="/dashboard">
                <i class="ri-calendar-todo-fill"></i> <span data-key="t-dashboard">{{ $t("scheduled_rides") }}</span>
            </Link>
          </li>
           <li class="nav-item mt-4">
            <Link class="nav-link menu-link" href="/dispatcher/rides_request">
                <i class="ri-taxi-fill"></i> <span data-key="t-dashboard">{{ $t("trip_request") }}</span>
            </Link>
          </li>
      </div>
    </ul>
  </template>
</BContainer></template>
  <style scoped>
  @font-face {
  font-family: "Zona Pro";
  src: url("/assets/fonts/ZonaPro-Bold.woff2") format("woff2"),
       url("/assets/fonts/ZonaPro-Bold.woff") format("woff");
  font-weight: normal;
  font-style: normal;
}
.navbar-nav {
  font-family: "Zona Pro", sans-serif;
}
.ltr .navbar-menu .navbar-nav .nav-link{
  font-family: "Zona Pro", sans-serif;
  font-weight: bold;
}
.ltr .navbar-menu .navbar-nav .nav-sm .nav-link:before {
    content: "";
    width: 8px;
    height: 8px;
    border-radius: 50%;
    position: absolute;
    left: 2px;
    top: 14.5px;
    opacity: 0.8;
}
:is([data-sidebar=light]) .navbar-menu .navbar-nav .menu-link.active{
    border-top-left-radius: 12px !important;
    border-bottom-left-radius: 12px !important;
    margin-left: 15px !important;
}
:is([data-sidebar=light]) .navbar-menu .navbar-nav .nav-link.active{
  background-color: var(--dispatcher_sidebar_txt_color) !important;
}
.ltr .navbar-menu .navbar-nav .nav-link:hover {
  /* color: #fff !important; */
   color: var(--dispatcher_sidebar_txt_color) !important;
}
.ltr .navbar-menu{
  background-color: var(--dispatcher_sidebar_color) !important;
}
@media only screen and (max-width: 769px) {
.booking{
    border-right: none;
    max-height:100%;
    overflow-y: auto;
}
}
</style>