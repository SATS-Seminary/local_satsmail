/*
South African Theological Seminary
 */

export function blur(node: HTMLElement, handler: () => void) {
    function handleFocusOut(event: FocusEvent) {
        if (event.relatedTarget instanceof Node && !node.contains(event.relatedTarget)) {
            handler();
        }
    }

    function handleMouseDown(event: Event) {
        if (event.target instanceof Node && !node.contains(event.target)) {
            handler();
        }
    }

    function handleFocusIn(event: FocusEvent) {
        // Catches focus moving into elements that swallow click events (e.g. TinyMCE iframes),
        // where mousedown on the parent document never fires.
        if (event.target instanceof Node && !node.contains(event.target)) {
            handler();
        }
    }

    document.addEventListener('mousedown', handleMouseDown, { capture: true, passive: true });
    document.addEventListener('focusin', handleFocusIn, { capture: true, passive: true });
    node.addEventListener('focusout', handleFocusOut);

    return {
        update(newHandler: () => void) {
            handler = newHandler;
        },
        destroy() {
            document.removeEventListener('mousedown', handleMouseDown, { capture: true });
            document.removeEventListener('focusin', handleFocusIn, { capture: true });
            node.removeEventListener('focusout', handleFocusOut);
        },
    };
}

