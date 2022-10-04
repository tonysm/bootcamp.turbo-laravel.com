import { Controller } from "@hotwired/stimulus"
import {leave, toggle} from 'el-transition'

// Connects to data-controller="dropdown"
export default class extends Controller {
    static targets = ['content'];
    static classes = ['css'];

    toggle() {
        toggle(this.contentTarget);
    }

    close(event) {
        if (! event.target.matches('a')) return;

        leave(this.contentTarget);
    }
}
