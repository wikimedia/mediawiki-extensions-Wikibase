# 11) Exception Handling and \@throws Annotations in PHP Doc Blocks {#rest_adr_0011}

Date: 2023-05-31

## Status

accepted

## Context

The Wikibase Product Platform team has repeatedly faced some confusion about the appropriate use of `@throws` annotations in doc blocks. We have discussed how to handle them, considering the need for meaningful exceptions and consistent annotations. The decision revolves around distinguishing between exceptions directly thrown by a method and those emitted from lower-levels.

## Decision

The team has agreed on the following guideline for annotating exceptions in `@throws` annotations:
 * Exceptions that are directly thrown by a method should always be annotated in the doc block, unless they are not expected to be caught (e.g. LogicExceptions or some InvalidArgumentExceptions).
 * Generally, exceptions emitted from lower-level layers should either be handled by the calling method, or caught and re-thrown to make them more meaningful by placing them into their new context. This also avoids unnecessary disclosure of information about underlying layers.
 * If an exception is still relevant to the class's level of abstraction, it can pass through without being modified. In this situation, the calling method's doc block should also include a `@throws` annotation.
