\name{leap.template.get}
\alias{leap.template.get}
\title{
    Template object retrieval
}
\description{
    Gets template object.
}
\usage{
    leap.template.get(templateID, workspaceID = leap$workspaceID)
}
\arguments{
    \item{templateID}{
        Template ID or name.
    }
    \item{workspaceID}{
        Leap workspace ID where template is located.
    }
}
\details{
}
\value{
    List with template object properties:
    \item{id }{template object ID}
    \item{name}{template object name}
    \item{head}{head tag contents}
    \item{HTML}{template HTML}
    \item{effect_show}{template transition show effect}
    \item{effect_show_options}{template transition show effect options}
    \item{effect_hide}{template transition hide effect}
    \item{effect_hide_options}{template transition hide effect options}
}
\references{
}
\author{
    Kooky Whiz
}
\note{
}

\seealso{
}
\examples{
    template1 <- leap.template.get(1)
    template2 <- leap.template.get("some_other_template")
}

\keyword{template}
\keyword{HTML}
