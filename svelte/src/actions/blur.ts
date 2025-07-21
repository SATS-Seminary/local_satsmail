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

    document.addEventListener('mousedown', handleMouseDown, { capture: true, passive: true });
    node.addEventListener('focusout', handleFocusOut);

    return {
        update(newHandler: () => void) {
            handler = newHandler;
        },
        destroy() {
            document.removeEventListener('mousedown', handleMouseDown, { capture: true });
            node.removeEventListener('focusout', handleFocusOut);
        },
    };
}

