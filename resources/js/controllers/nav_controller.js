import { Controller } from "@hotwired/stimulus"

// Connects to data-controller="nav"
export default class extends Controller {
    connect() {
        this.highlightCurrentNav()
        this.expandCurrentNavGroup()
    }

    highlightCurrentNav() {
        Array.from(this.element.querySelectorAll('a')).forEach(link => {
            if (link.hostname === location.hostname
                && (link.pathname === location.pathname || (link.pathname === '/introduction' && location.pathname === '/'))
            ) {
                link.classList.add('active')

                if (link.parentNode.tagName === 'LI') {
                    link.parentNode.parentNode.parentNode.classList.add('sub--on')
                }
            }
        })
    }

    expandCurrentNavGroup() {
        Array.from(this.element.querySelectorAll('h2')).forEach(el => {
            if (el.children.length > 1) {
                return
            }

            el.addEventListener('click', (e) => {
                const active = el.parentNode.classList.contains('sub--on');

                [...this.element.querySelectorAll('ul li')].forEach(el => el.classList.remove('sub--on'));

                if (! active) {
                    el.parentNode.classList.add('sub--on');
                }
            })
        })
    }
}
